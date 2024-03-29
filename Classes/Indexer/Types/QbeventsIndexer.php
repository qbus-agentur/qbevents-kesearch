<?php
namespace Qbus\QbeventsKesearch\Indexer\Types;

use Tpwd\KeSearch\Lib\SearchHelper;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * QbeventsIndexer
 *
 * @author Benjamin Franzke <bfr@qbus.de>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class QbeventsIndexer
{
    /**
     * @param  array  $param
     * @param  object $pObj
     * @return void
     */
    public function registerIndexerConfiguration(&$params, $pObj)
    {
        // add tt_producs to the "type" field
        $params['items'][] = array(
            'qbevents indexer',
            'qbevents',
            'EXT:qbevents_kesearch/ext_icon.gif'
        );
    }

    /**
     * Custom qbevents indexer for ke_search
     *
     * @param  array  $indexerConfig Configuration from TYPO3 Backend
     * @param  array  $indexerObject Reference to indexer class.
     * @return string Output.
     */
    public function customIndexer(&$indexerConfig, &$indexerObject)
    {
        $content = '';

        if ($indexerConfig['type'] !== 'qbevents') {
            return '';
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_qbevents_domain_model_event');

        $res = $queryBuilder
            ->select('*')
            ->from('tx_qbevents_domain_model_eventdate', 'date')
            ->join(
                'date',
                'tx_qbevents_domain_model_event',
                'event',
                $queryBuilder->expr()->eq('date.event', $queryBuilder->quoteIdentifier('event.uid'))
            )
            ->where(...[
                $queryBuilder->expr()->in('date.pid', GeneralUtility::trimExplode(',', $indexerConfig['sysfolder'], true)),
                //'tx_qbevents_domain_model_eventdate.start >= UNIX_TIMESTAMP(NOW())',
                $queryBuilder->expr()->comparison($queryBuilder->quoteIdentifier('date.start'), '>=', time()),
            ])
            ->orderBy('date.start', 'ASC')
            ->execute();


        $count = $res->rowCount();

        if ($count) {
            while (($record = $res->fetch())) {
                /* Compile the information which should go into the index
                   the field names depend on the table you want to index. */
                $title = strip_tags($record['title']);
                //$subtitle = strip_tags($record['subtitle']);
                $content = strip_tags($record['teaser']);

                if (isset($record['start']) && intval($record['start']) > 0) {
                    $start = date('d.m.Y H:i', intval($record['start']));
                    $title .= ' – ' . $start;
                }

                $fullContent = $content;

                $params = '&tx_qbevents_events[action]=show&tx_qbevents_events[controller]=EventDate&tx_qbevents_events[date]=' . $record['uid'];

                $tags = '';

                if ($indexerConfig['index_use_page_tags']) {
                    $tags = $this->pageRecords[intval($record['pid'])]['tags'];
                }

                SearchHelper::makeTags($tags, ['event']);

                // make tags from assigned categories
                $categories = SearchHelper::getCategories($record['event'], 'tx_qbevents_domain_model_event');
                SearchHelper::makeTags($tags, $categories['title_list']);

                // assign categories as generic tags (eg. "syscat123")
                SearchHelper::makeSystemCategoryTags($tags, $record['event'], 'tx_qbevents_domain_model_event');

                $additionalFields = array(
                    'sortdate' => (int)$record['crdate'],
                    'orig_uid' => (int)$record['uid'],
                    'orig_pid' => (int)$record['pid'],
                );

                // hook for custom modifications of the indexed data, e.g. the tags
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['qbevents_kesearch']['modifyQbeventsIndexEntry'])) {
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['qbevents_kesearch']['modifyQbeventsIndexEntry'] as $_classRef) {
                        $_procObj = & GeneralUtility::makeInstance($_classRef);
                        $_procObj->modifyQbeventsIndexEntry(
                            $title,
                            $fullContent,
                            $params,
                            $tags,
                            $record,
                            $additionalFields,
                            $indexerConfig,
                            $this
                        );
                    }
                }

                // Store the information in the index
                $indexerObject->storeInIndex(
                    $indexerConfig['storagepid'], // storage PID
                    $title, // record title
                    'qbevents', // content type
                    $indexerConfig['targetpid'], // target PID: where is the single view?
                    $fullContent, // indexed content, includes the title (linebreak after title)
                    $tags, // tags for faceted search
                    $params, // typolink params for singleview
                    $abstract, // abstract; shown in result list if not empty
                    $record['sys_language_uid'], // language uid
                    $record['starttime'], // starttime
                    $record['endtime'], // endtime
                    $record['fe_group'], // fe_group
                    false, // debug only?
                    $additionalFields // additionalFields
                );
            }
            $content = '<p><b>qbevents Indexer "' . $indexerConfig['title'] . '": ' . $count . ' Elements have been indexed.<b></p>';
        }

        return $content;
    }
}
