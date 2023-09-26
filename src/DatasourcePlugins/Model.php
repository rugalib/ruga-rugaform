<?php

declare(strict_types=1);


namespace Ruga\Rugaform\DatasourcePlugins;


use Laminas\Db\Sql\Select;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Ruga\Db\Adapter\AdapterInterface;
use Ruga\Db\Row\RowInterface;
use Ruga\Db\Table\AbstractTable;
use Ruga\Db\Table\TableInterface;
use Ruga\Rugaform\Exception\InvalidTableException;
use Ruga\Rugaform\Middleware\RugaformRequest;
use Ruga\Rugaform\Middleware\RugaformResponse;

/**
 * The model plugin handles all the requests directed at the already existing database model.
 * Class name of the table is expected at the second position in the uri of the serverSide request.
 *
 * @see      ModelFactory
 * @author   Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 */
class Model implements DatasourcePluginInterface
{
    /** @var AdapterInterface */
    private $adapter;
    
    
    
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }
    
    
    
    /**
     * Handle the request from datatables and return the response.
     *
     * @param RugaformRequest $rugaformRequest
     *
     * @return RugaformResponse
     * @throws \Exception
     */
    public function process(RugaformRequest $rugaformRequest): RugaformResponse
    {
        \Ruga\Log::functionHead($this);
        
        $rugaformResponse = new RugaformResponse($rugaformRequest);
        /** @var AbstractTable $table */
        $table = $this->getModelFromRequest($rugaformRequest);
        
        $newRowCreated=true;
        
        // Get row_id from request
        $row_id = $rugaformRequest->getUniqueid();
        $rugaformResponse->setUniqueid($row_id);
        $row = $table->findById($row_id)->current();
        
        // No Object found => create new
        if (!$row) {
            $row = $table->createRow($rugaformRequest->getData());
            $rugaformResponse->addMessage("New Object {$row->type} created.");
            \Ruga\Log::log_msg("New Object {$row->type} created.");
            $rugaformResponse->setQuery(get_class($table) . "->createRow(): " . get_class($row));
        } else {
            $rugaformResponse->addMessage("Found Object {$row->type} {$row->idname}");
            \Ruga\Log::log_msg("Found Object {$row->type} {$row->idname}");
            $rugaformResponse->setQuery(get_class($table) . "->findById(\"{$row_id}\")->current(): " . get_class($row));
            $newRowCreated=false;
        }
        
        $rugaformResponse->setData($row->toArray());
        
        
        try {
            if ($rugaformRequest->isFormGetRequest()) {
                \Ruga\Log::log_msg("GET {$row->idname}");
                if(!$row->isNew()) $rugaformResponse->setUniqueid($row->uniqueid);
                $rugaformResponse->setData($row->toArray());
                $rugaformResponse->setFinalMessage('Datensatz neu gelesen.', 'INFORMATIONAL', 'RESULT');
            
            } elseif ($rugaformRequest->isFormDeleteRequest()) {
                \Ruga\Log::log_msg("DELETE {$row->idname}");
                try {
                    $row->delete();
                    $row = null;
                    $rugaformResponse->setSuccessUri('');
                    $rugaformResponse->setFinalMessage('Erfolgreich gelöscht.', 'INFORMATIONAL', 'RESULT');
                } catch (\Exception $e) {
                    $rugaformResponse->setFinalMessage($e->getMessage(), 'ERROR', 'RESULT');
                }
                
            } elseif ($rugaformRequest->isFormSetFavourite()) {
                \Ruga\Log::log_msg("POST SET FAVOURITE {$row->idname}");
                /*
                $setFavourite=$params['setfavourite']=='true';
                if( $setFavourite ) Ruga_Log::addLog("SET Favourite {$row->idname}");
                else Ruga_Log::addLog("UNSET Favourite {$row->idname}");
    
                $this->row->setFavourite($setFavourite);
    
                $jsonResult->addLogFinal(
                    'Favorit ' . ($setFavourite ? 'gesetzt' : 'entfernt') . '.',
                    Ruga_Log_Severity::INFORMATIONAL,
                    Ruga_Log_Type::RESULT,
                    $row
                );
                */
            } elseif ($rugaformRequest->isFormCreateRow() || $rugaformRequest->isFormUpdateRow()) {
                \Ruga\Log::log_msg("POST {$row->idname}");
                try {
                    /** @var RowInterface $row */
                    if ($row->isReadOnly()) {
                        // TODO WriteProtectionException
//                        throw new Exception\WriteProtectionException("Row {$row->idname} is write protected.");
                        throw new \Exception("Row {$row->idname} is write protected.");
                    }
                    
                    $params = $rugaformRequest->getData();
                    foreach ($params as $param => $val) {
                        try {
                            $row->$param = $val;
                        } catch (\InvalidArgumentException $e) {
                            \Ruga\Log::log_msg(
                                "FAILED saving value " . str_replace(
                                    "\n",
                                    '',
                                    var_export($val, true)
                                ) . " to attribute '{$param}' of {$row->idname}. {$e->getMessage()}"
                            );
                        }
                    }
                    
                    $row->save();
                    
                    
                    
                    
                    $rugaformResponse->setSuccessUri('');
                    if($newRowCreated) {
                        // TODO This is not cool
                        $referer=implode('', $rugaformRequest->getRequest()->getHeaders()['referer'] ?? []);
                        $rugaformResponse->setSuccessUri(str_replace('new', $row->PK, $referer));
                    }
                    
                    $rugaformResponse->setUniqueid($row->uniqueid);
                    $rugaformResponse->setData($row->toArray());
                    $rugaformResponse->setFinalMessage(
                        "Erfolgreich gespeichert. {$row->idname}",
                        'INFORMATIONAL',
                        'RESULT'
                    );
                } catch (\Exception $e) {
                    $rugaformResponse->setFinalMessage($e->getMessage(), 'ERROR', 'RESULT');
                    \Ruga\Log::addLog($e);
                }
            } else {
                throw new \Exception('Method not supported.');
            }
        } catch (\Exception $e) {
            $rugaformResponse->setFinalMessage($e->getMessage(), 'ERROR', 'RESULT');
            \Ruga\Log::addLog($e);
        }
        
        
        return $rugaformResponse;
        
        /** @var Select $select */
        $select = $table->getSql()->select();
//        $datatablesResponse->setQuery($select->getSqlString($table->getAdapter()->getPlatform()));
        
        // Customize sql
        $customSqlSelectName = $this->getCustomSqlSelectNameFromRequest($rugaformRequest);
        if ($customSqlSelectName && method_exists($table, 'customizeSqlSelectFromRequest')) {
//            \Ruga\Log::log_msg("\$customSqlSelectName={$customSqlSelectName}");
            $table->customizeSqlSelectFromRequest($customSqlSelectName, $select, $rugaformRequest->getRequest());
        }
        
        // Reset parts used by datatables
//        $select->reset(Select::GROUP);
        $select->reset(Select::ORDER);
        $select->reset(Select::LIMIT);
        $select->reset(Select::OFFSET);
        
        // Count records without filter and search applied
//        $datatablesResponse->setRecordsTotal(count($table->selectWith($select)));
//        $datatablesResponse->setQuery($select->getSqlString($table->getAdapter()->getPlatform()));
        
        // Apply filter form
//        $filter = $rugaformRequest->getFilter();
//        if (method_exists($table, 'applyFilterToSqlSelect')) {
////            \Ruga\Log::log_msg('$filter=' . print_r($filter, true));
//            $table->applyFilterToSqlSelect($filter, $select);
//            $datatablesResponse->setFilter($filter);
//        }
        
        // Count records with filter applied
//        $datatablesResponse->setRecordsFiltered(count($table->selectWith($select)));
        $rugaformResponse->setQuery($select->getSqlString($table->getAdapter()->getPlatform()));
        
        
        /** @var RowInterface $row */
        foreach ($table->selectWith($select) as $row) {
            $rugaformResponse->addRow($row->toArray());
        }
        
        return $rugaformResponse;
    }
    
    
    
    /**
     * Find and instantiate the model by information from the serverSide request.
     *
     * @param RugaformRequest $rugaformRequest
     *
     * @return TableInterface
     */
    public function getModelFromRequest(RugaformRequest $rugaformRequest): TableInterface
    {
        if (count($rugaformRequest->getRequestPathParts()) < 2) {
            throw new InvalidTableException("No table specified", 400);
        }
        
        $modelName = $rugaformRequest->getRequestPathParts()[1] ?? null;
        
        try {
            return $this->adapter->tableFactory($modelName);
        } catch (ServiceNotFoundException $e) {
            throw new InvalidTableException("Model {$modelName} not found", 404);
        }
    }
    
    
    
    /**
     * Find the name of the customization for the Sql Select object.
     *
     * @param RugaformRequest $rugaformRequest
     *
     * @return string|null
     */
    public function getCustomSqlSelectNameFromRequest(RugaformRequest $rugaformRequest): ?string
    {
        return $rugaformRequest->getRequestPathParts()[2] ?? null;
    }
    
}