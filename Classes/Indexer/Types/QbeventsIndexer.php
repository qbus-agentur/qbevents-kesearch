<?php
namespace Qbus\QbeventsKesearch\Indexer\Types;

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

        $fields = '*';
        $table = 'tx_qbevents_domain_model_event, tx_qbevents_domain_model_eventdate';

        $whereParts = [
            'tx_qbevents_domain_model_eventdate.event = tx_qbevents_domain_model_event.uid',
            'tx_qbevents_domain_model_eventdate.pid IN (' . $indexerConfig['sysfolder'] . ')',
            'tx_qbevents_domain_model_eventdate.hidden = 0',
            'tx_qbevents_domain_model_eventdate.deleted = 0',
            'tx_qbevents_domain_model_eventdate.start >= UNIX_TIMESTAMP(NOW())',
        ];

        $where = implode(' AND ', $whereParts);

        $groupBy = '';
        $orderBy = 'tx_qbevents_domain_model_eventdate.start ASC';
        $limit = '';
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $table, $where, $groupBy, $orderBy, $limit);
        $count = $GLOBALS['TYPO3_DB']->sql_num_rows($res);

        if ($count) {
            while ($record = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
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

                \tx_kesearch_helper::makeTags($tags, ['event']);

                // make tags from assigned categories
                $categories = \tx_kesearch_helper::getCategories($record['event'], 'tx_qbevents_domain_model_event');
                \tx_kesearch_helper::makeTags($tags, $categories['title_list']);

                // assign categories as generic tags (eg. "syscat123")
                \tx_kesearch_helper::makeSystemCategoryTags($tags, $record['event'], 'tx_qbevents_domain_model_event');

                $additionalFields = array(
                    'sortdate' => (int)$record['crdate'],
                    'orig_uid' => (int)$record['uid'],
                    'orig_pid' => (int)$record['pid'],
                    'sortdate' => (int)$record['datetime'],
                );

                // hook for custom modifications of the indexed data, e.g. the tags
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['qbevents_kesearch']['modifyQbeventsIndexEntry'])) {
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['qbevents_kesearch']['modifyQbeventsIndexEntry'] as $_classRef) {
                        $_procObj = & \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
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
