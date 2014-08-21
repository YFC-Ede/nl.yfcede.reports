<?php

/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.4                                                |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2013                                |
  +--------------------------------------------------------------------+
  | This file is a part of CiviCRM.                                    |
  |                                                                    |
  | CiviCRM is free software; you can copy, modify, and distribute it  |
  | under the terms of the GNU Affero General Public License           |
  | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
  |                                                                    |
  | CiviCRM is distributed in the hope that it will be useful, but     |
  | WITHOUT ANY WARRANTY; without even the implied warranty of         |
  | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
  | See the GNU Affero General Public License for more details.        |
  |                                                                    |
  | You should have received a copy of the GNU Affero General Public   |
  | License and the CiviCRM Licensing Exception along                  |
  | with this program; if not, contact CiviCRM LLC                     |
  | at info[AT]civicrm[DOT]org. If you have questions about the        |
  | GNU Affero General Public License or the licensing of CiviCRM,     |
  | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
  +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Reports_Form_Report_CRM_Report_Form_Evaluaties extends CRM_Report_Form {

  protected $_selectAliasesTotal = array();
  protected $_customGroupExtends = array(
    'Activity'
  );
  protected $_nonDisplayFields = array();
  
  protected $_add2groupSupported = FALSE;

  function __construct() {
    // There could be multiple contacts. We not clear on which contact id to display.
    // Lets hide it for now.
    $this->_exposeContactID = FALSE;

    $config = CRM_Core_Config::singleton();
    $campaignEnabled = in_array("CiviCampaign", $config->enableComponents);
    if ($campaignEnabled) {
      $getCampaigns = CRM_Campaign_BAO_Campaign::getPermissionedCampaigns(NULL, NULL, TRUE, FALSE, TRUE);
      $this->activeCampaigns = $getCampaigns['campaigns'];
      asort($this->activeCampaigns);
      $this->engagementLevels = CRM_Campaign_PseudoConstant::engagementLevel();
    }
    $this->activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, FALSE, FALSE, 'label', TRUE);
    asort($this->activityTypes);

    $this->_columns = array(
      'civicrm_activity' =>
      array(
        'dao' => 'CRM_Activity_DAO_Activity',
        'fields' =>
        array(
          'id' =>
          array(
            'no_display' => TRUE,
            'title' => ts('Activity ID'),
            'required' => TRUE,
          ),
          'source_record_id' =>
          array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'activity_type_id' =>
          array('title' => ts('Activity Type'),
            'type' => CRM_Utils_Type::T_STRING,
            'required' => true,
            'no_display' => true,
          ),
          'activity_subject' =>
          array('title' => ts('Subject'),
            'default' => FALSE,
          ),
          'activity_date_time' =>
          array('title' => ts('Activity Date'),
            'required' => TRUE,
          ),
          'status_id' =>
          array('title' => ts('Activity Status'),
            'default' => FALSE,
            'type' => CRM_Utils_Type::T_STRING,
          ),
          'duration' =>
          array('title' => ts('Duration'),
            'type' => CRM_Utils_Type::T_INT,
          ),
          'details' => array(
            'title' => ts('Activity Details'),
          )
        ),
        'filters' => array(
          'activity_date_time' => array(
            'default' => 'this.month',
            'operatorType' => CRM_Report_Form::OP_DATE,
          ),
          'activity_subject' =>
          array('title' => ts('Activity Subject')),
          'activity_type_id' =>
          array('title' => ts('Activity Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->activityTypes,
          ),
          'status_id' =>
          array('title' => ts('Activity Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::activityStatus(),
          ),
          'details' => array(
            'title' => ts('Activity Details'),
            'type' => CRM_Utils_Type::T_TEXT,
          )
        ),
        'order_bys' => array(
          'activity_date_time' =>
          array('title' => ts('Activity Date'), 'default_weight' => '1', 'dbAlias' => 'civicrm_activity_activity_date_time'),
          'activity_type_id' =>
          array('title' => ts('Activity Type'), 'dbAlias' => 'civicrm_activity_activity_type_id'),
        ),
        'grouping' => 'activity-fields',
        'alias' => 'activity',
      ),
    );

    if ($campaignEnabled) {
      // Add display column and filter for Survey Results, Campaign and Engagement Index if CiviCampaign is enabled

      $this->_columns['civicrm_activity']['fields']['result'] = array(
        'title' => 'Survey Result',
        'default' => 'false',
      );
      $this->_columns['civicrm_activity']['filters']['result'] = array('title' => ts('Survey Result'),
        'operator' => 'like',
        'type' => CRM_Utils_Type::T_STRING,
      );
      if (!empty($this->activeCampaigns)) {
        $this->_columns['civicrm_activity']['fields']['campaign_id'] = array(
          'title' => 'Campaign',
          'default' => 'false',
        );
        $this->_columns['civicrm_activity']['filters']['campaign_id'] = array('title' => ts('Campaign'),
          'operatorType' => CRM_Report_Form::OP_MULTISELECT,
          'options' => $this->activeCampaigns,
        );
      }
      if (!empty($this->engagementLevels)) {
        $this->_columns['civicrm_activity']['fields']['engagement_level'] = array(
          'title' => 'Engagement Index',
          'default' => 'false',
        );
        $this->_columns['civicrm_activity']['filters']['engagement_level'] = array('title' => ts('Engagement Index'),
          'operatorType' => CRM_Report_Form::OP_MULTISELECT,
          'options' => $this->engagementLevels,
        );
      }
    }
    $this->_groupFilter = TRUE;
    $this->_tagFilter = TRUE;
    parent::__construct();
  }

  /**
   * Override this function to include memo fields
   * 
   * @param type $addFields
   * @param type $permCustomGroupIds
   * @return type
   */
  function addCustomDataToColumns($addFields = TRUE, $permCustomGroupIds = array()) {
    if (empty($this->_customGroupExtends)) {
      return;
    }
    if (!is_array($this->_customGroupExtends)) {
      $this->_customGroupExtends = array($this->_customGroupExtends);
    }
    $customGroupWhere = '';
    if (!empty($permCustomGroupIds)) {
      $customGroupWhere = "cg.id IN (" . implode(',', $permCustomGroupIds) . ") AND";
    }
    $sql = "
SELECT cg.table_name, cg.title, cg.extends, cf.id as cf_id, cf.label,
       cf.column_name, cf.data_type, cf.html_type, cf.option_group_id, cf.time_format
FROM   civicrm_custom_group cg
INNER  JOIN civicrm_custom_field cf ON cg.id = cf.custom_group_id
WHERE cg.extends IN ('" . implode("','", $this->_customGroupExtends) . "') AND
      {$customGroupWhere}
      cg.is_active = 1 AND
      cf.is_active = 1
ORDER BY cg.weight, cf.weight";
    $customDAO = CRM_Core_DAO::executeQuery($sql);

    $curTable = NULL;
    while ($customDAO->fetch()) {
      if ($customDAO->table_name != $curTable) {
        $curTable = $customDAO->table_name;
        $curFields = $curFilters = array();

        // dummy dao object
        $this->_columns[$curTable]['dao'] = 'CRM_Contact_DAO_Contact';
        $this->_columns[$curTable]['extends'] = $customDAO->extends;
        $this->_columns[$curTable]['grouping'] = $customDAO->table_name;
        $this->_columns[$curTable]['group_title'] = $customDAO->title;

        foreach (array(
      'fields', 'filters', 'group_bys') as $colKey) {
          if (!array_key_exists($colKey, $this->_columns[$curTable])) {
            $this->_columns[$curTable][$colKey] = array();
          }
        }
      }
      $fieldName = 'custom_' . $customDAO->cf_id;

      if ($addFields) {
        // this makes aliasing work in favor
        $curFields[$fieldName] = array(
          'name' => $customDAO->column_name,
          'title' => $customDAO->label,
          'dataType' => $customDAO->data_type,
          'htmlType' => $customDAO->html_type,
        );
      }
      if ($this->_customGroupFilters) {
        // this makes aliasing work in favor
        $curFilters[$fieldName] = array(
          'name' => $customDAO->column_name,
          'title' => $customDAO->label,
          'dataType' => $customDAO->data_type,
          'htmlType' => $customDAO->html_type,
        );
      }

      switch ($customDAO->data_type) {
        case 'Date':
          // filters
          $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_DATE;
          $curFilters[$fieldName]['type'] = CRM_Utils_Type::T_DATE;
          // CRM-6946, show time part for datetime date fields
          if ($customDAO->time_format) {
            $curFields[$fieldName]['type'] = CRM_Utils_Type::T_TIMESTAMP;
          }
          break;

        case 'Memo':
          $curFields[$fieldName]['nl2br'] = true;
          break;  
          
        case 'Boolean':
          $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_SELECT;
          $curFilters[$fieldName]['options'] = array('' => ts('- select -'),
            1 => ts('Yes'),
            0 => ts('No'),
          );
          $curFilters[$fieldName]['type'] = CRM_Utils_Type::T_INT;
          break;

        case 'Int':
          $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_INT;
          $curFilters[$fieldName]['type'] = CRM_Utils_Type::T_INT;
          break;

        case 'Money':
          $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_FLOAT;
          $curFilters[$fieldName]['type'] = CRM_Utils_Type::T_MONEY;
          break;

        case 'Float':
          $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_FLOAT;
          $curFilters[$fieldName]['type'] = CRM_Utils_Type::T_FLOAT;
          break;

        case 'String':
          $curFilters[$fieldName]['type'] = CRM_Utils_Type::T_STRING;

          if (!empty($customDAO->option_group_id)) {
            if (in_array($customDAO->html_type, array(
                  'Multi-Select', 'AdvMulti-Select', 'CheckBox'))) {
              $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_MULTISELECT_SEPARATOR;
            } else {
              $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_MULTISELECT;
            }
            if ($this->_customGroupFilters) {
              $curFilters[$fieldName]['options'] = array();
              $ogDAO = CRM_Core_DAO::executeQuery("SELECT ov.value, ov.label FROM civicrm_option_value ov WHERE ov.option_group_id = %1 ORDER BY ov.weight", array(1 => array($customDAO->option_group_id, 'Integer')));
              while ($ogDAO->fetch()) {
                $curFilters[$fieldName]['options'][$ogDAO->value] = $ogDAO->label;
              }
            }
          }
          break;

        case 'StateProvince':
          if (in_array($customDAO->html_type, array(
                'Multi-Select State/Province'))) {
            $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_MULTISELECT_SEPARATOR;
          } else {
            $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_MULTISELECT;
          }
          $curFilters[$fieldName]['options'] = CRM_Core_PseudoConstant::stateProvince();
          break;

        case 'Country':
          if (in_array($customDAO->html_type, array(
                'Multi-Select Country'))) {
            $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_MULTISELECT_SEPARATOR;
          } else {
            $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_MULTISELECT;
          }
          $curFilters[$fieldName]['options'] = CRM_Core_PseudoConstant::country();
          break;

        case 'ContactReference':
          $curFilters[$fieldName]['type'] = CRM_Utils_Type::T_STRING;
          $curFilters[$fieldName]['name'] = 'display_name';
          $curFilters[$fieldName]['alias'] = "contact_{$fieldName}_civireport";

          $curFields[$fieldName]['type'] = CRM_Utils_Type::T_STRING;
          $curFields[$fieldName]['name'] = 'display_name';
          $curFields[$fieldName]['alias'] = "contact_{$fieldName}_civireport";
          break;
        
        default:
          $curFields[$fieldName]['type'] = CRM_Utils_Type::T_STRING;
          $curFilters[$fieldName]['type'] = CRM_Utils_Type::T_STRING;
      }

      if (!array_key_exists('type', $curFields[$fieldName])) {
        $curFields[$fieldName]['type'] = CRM_Utils_Array::value('type', $curFilters[$fieldName], array());
      }

      if ($addFields) {
        $this->_columns[$curTable]['fields'] = array_merge($this->_columns[$curTable]['fields'], $curFields);
      }
      if ($this->_customGroupFilters) {
        $this->_columns[$curTable]['filters'] = array_merge($this->_columns[$curTable]['filters'], $curFilters);
      }
      if ($this->_customGroupGroupBy) {
        $this->_columns[$curTable]['group_bys'] = array_merge($this->_columns[$curTable]['group_bys'], $curFields);
      }
    }
  }

  function select($recordType = NULL) {
    parent::select();

    foreach ($this->_nonDisplayFields as $fieldName) {
      unset($this->_columnHeaders[$fieldName]);
    }

    if (empty($this->_selectAliasesTotal)) {
      $this->_selectAliasesTotal = $this->_selectAliases;
    }

    $this->_select = "SELECT " . implode(', ', $this->_selectClauses) . " ";
  }

  function from($recordType) {   
      $this->_from = "
        FROM civicrm_activity {$this->_aliases['civicrm_activity']}";    
    $this->addAddressFromClause();
  }

  function where($recordType = NULL) {
    $this->_where = " WHERE {$this->_aliases['civicrm_activity']}.is_test = 0 AND
                                {$this->_aliases['civicrm_activity']}.is_deleted = 0 AND
                                {$this->_aliases['civicrm_activity']}.is_current_revision = 1";

    $clauses = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {

        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if ($fieldName != 'contact_' . $recordType &&
              (strstr($fieldName, '_target') ||
              strstr($fieldName, '_assignee') ||
              strstr($fieldName, '_source')
              )
          ) {
            continue;
          }
          if (CRM_Utils_Array::value('type', $field) & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
          } else {
            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
            if ($op && ($op != 'nnll' || $op != 'nll')) {
              $clause = $this->whereClause($field, $op, CRM_Utils_Array::value("{$fieldName}_value", $this->_params), CRM_Utils_Array::value("{$fieldName}_min", $this->_params), CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
              );
            }
          }

          if ($field['name'] == 'current_user') {
            if (CRM_Utils_Array::value("{$fieldName}_value", $this->_params) == 1) {
              // get current user
              $session = CRM_Core_Session::singleton();
              if ($contactID = $session->get('userID')) {
                $clause = "{$this->_aliases['civicrm_activity_contact']}.activity_id IN
                           (SELECT activity_id FROM civicrm_activity_contact WHERE contact_id = {$contactID})";
              } else {
                $clause = NULL;
              }
            } else {
              $clause = NULL;
            }
          }
          if (!empty($clause)) {
            $clauses[] = $clause;
          }
        }
      }
    }

    if (empty($clauses)) {
      $this->_where .= " ";
    } else {
      $this->_where .= " AND " . implode(' AND ', $clauses);
    }

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }
  }

  function groupBy() {
    $this->_groupBy = "GROUP BY {$this->_aliases['civicrm_activity']}.id";
  }

  function buildACLClause($tableAlias = 'contact_a') {
    //override for ACL( Since Contact may be source
    //contact/assignee or target also it may be null )

    if (CRM_Core_Permission::check('view all contacts')) {
      $this->_aclFrom = $this->_aclWhere = NULL;
      return;
    }

    $session = CRM_Core_Session::singleton();
    $contactID = $session->get('userID');
    if (!$contactID) {
      $contactID = 0;
    }
    $contactID = CRM_Utils_Type::escape($contactID, 'Integer');

    CRM_Contact_BAO_Contact_Permission::cache($contactID);
    $clauses = array();
    foreach ($tableAlias as $k => $alias) {
      $clauses[] = " INNER JOIN civicrm_acl_contact_cache aclContactCache_{$k} ON ( {$alias}.id = aclContactCache_{$k}.contact_id OR {$alias}.id IS NULL ) AND aclContactCache_{$k}.user_id = $contactID ";
    }

    $this->_aclFrom = implode(" ", $clauses);
    $this->_aclWhere = NULL;
  }

  function add2group($groupID) {
    if (CRM_Utils_Array::value("contact_target_op", $this->_params) == 'nll') {
      CRM_Core_Error::fatal(ts('Current filter criteria didn\'t have any target contact to add to group'));
    }

    $query = "{$this->_select}
FROM civireport_activity_temp_target tar
GROUP BY civicrm_activity_id {$this->_having} {$this->_orderBy}";
    $select = 'AS addtogroup_contact_id';
    $query = str_ireplace('AS civicrm_contact_contact_target_id', $select, $query);
    $dao = CRM_Core_DAO::executeQuery($query);

    $contactIDs = array();
    // Add resulting contacts to group
    while ($dao->fetch()) {
      if ($dao->addtogroup_contact_id) {
        $contact_id = explode(';', $dao->addtogroup_contact_id);
        if ($contact_id[0]) {
          $contactIDs[$contact_id[0]] = $contact_id[0];
        }
      }
    }

    if (!empty($contactIDs)) {
      CRM_Contact_BAO_GroupContact::addContactsToGroup($contactIDs, $groupID);
      CRM_Core_Session::setStatus(ts("Listed contact(s) have been added to the selected group."), ts('Contacts Added'), 'success');
    } else {
      CRM_Core_Session::setStatus(ts("The listed records(s) cannot be added to the group."));
    }
  }

  function postProcess() {
    //$this->buildACLClause(array('civicrm_contact_source', 'civicrm_contact_target', 'civicrm_contact_assignee'));
    $this->beginPostProcess();

    $sql = $this->buildQuery(FALSE);
    $this->buildRows($sql, $rows);

    // format result set.
    $this->formatDisplay($rows);

    // assign variables to templates
    $this->doTemplateAssignment($rows);

    // do print / pdf / instance stuff if needed
    $this->endPostProcess($rows);
  }

  function alterDisplay(&$rows) {
    // custom code to alter rows

    $entryFound = FALSE;
    $activityType = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'label', TRUE);
    $activityStatus = CRM_Core_PseudoConstant::activityStatus();
    $viewLinks = FALSE;
    $seperator = CRM_Core_DAO::VALUE_SEPARATOR;
    $context = CRM_Utils_Request::retrieve('context', 'String', $this, FALSE, 'report');
    $actUrl = '';

    if (CRM_Core_Permission::check('access CiviCRM')) {
      $viewLinks = TRUE;
      $onHover = ts('View Contact Summary for this Contact');
      $onHoverAct = ts('View Activity Record');
    }
    
    $nl2br_fields = array();
    foreach($this->_columns as $table_name => $table) {
      foreach($table['fields'] as $key => $field) {
        if (isset($field['nl2br']) && $field['nl2br']) {
          $nl2br_fields[] = $table_name . '_'.$key;
        }
      }
    }
    

    foreach ($rows as $rowNum => $row) {
      foreach($nl2br_fields as $nl2brfield) {
        if (isset($row[$nl2brfield])) {
          $rows[$rowNum][$nl2brfield] = nl2br($row[$nl2brfield]);
        }
      }
      // if we have an activity type, format the View Activity link for use in various columns
      if ($viewLinks && array_key_exists('civicrm_activity_activity_type_id', $row)) {
        // Check for target contact id(s) and use the first contact id in that list for view activity link if found,
        // else use source contact id

        $actActionLinks = CRM_Activity_Selector_Activity::actionLinks($row['civicrm_activity_activity_type_id'], CRM_Utils_Array::value('civicrm_activity_source_record_id', $rows[$rowNum]), FALSE, $rows[$rowNum]['civicrm_activity_id']
        );

        $actLinkValues = array(
          'id' => $rows[$rowNum]['civicrm_activity_id'],
          'cid' => 0,
          'cxt' => $context,
        );
        $actUrl = CRM_Utils_System::url($actActionLinks[CRM_Core_Action::VIEW]['url'], CRM_Core_Action::replace($actActionLinks[CRM_Core_Action::VIEW]['qs'], $actLinkValues), TRUE
        );
      }

      if (array_key_exists('civicrm_activity_activity_type_id', $row)) {
        if ($value = $row['civicrm_activity_activity_type_id']) {
          $rows[$rowNum]['civicrm_activity_activity_type_id'] = $activityType[$value];
          if ($viewLinks) {
            $rows[$rowNum]['civicrm_activity_activity_type_id_link'] = $actUrl;
            $rows[$rowNum]['civicrm_activity_activity_type_id_hover'] = $onHoverAct;
          }
          $entryFound = TRUE;
        }
      }

      if (array_key_exists('civicrm_activity_status_id', $row)) {
        if ($value = $row['civicrm_activity_status_id']) {
          $rows[$rowNum]['civicrm_activity_status_id'] = $activityStatus[$value];
          $entryFound = TRUE;
        }
      }

      if (array_key_exists('civicrm_activity_details', $row)) {
        if ($value = $row['civicrm_activity_details']) {
          $fullDetails = $rows[$rowNum]['civicrm_activity_details'];
          $rows[$rowNum]['civicrm_activity_details'] = substr($fullDetails, 0, strrpos(substr($fullDetails, 0, 80), ' '));
          if ($actUrl) {
            $rows[$rowNum]['civicrm_activity_details'] .= " <a href='{$actUrl}' title='{$onHoverAct}'>(more)</a>";
          }
          $entryFound = TRUE;
        }
      }

      if (array_key_exists('civicrm_activity_campaign_id', $row)) {
        if ($value = $row['civicrm_activity_campaign_id']) {
          $rows[$rowNum]['civicrm_activity_campaign_id'] = $this->activeCampaigns[$value];
          $entryFound = TRUE;
        }
      }

      if (array_key_exists('civicrm_activity_engagement_level', $row)) {
        if ($value = $row['civicrm_activity_engagement_level']) {
          $rows[$rowNum]['civicrm_activity_engagement_level'] = $this->engagementLevels[$value];
          $entryFound = TRUE;
        }
      }
      if (array_key_exists('civicrm_activity_activity_date_time', $row)) {
        $rows[$rowNum]['civicrm_activity_activity_date_time_link'] = $actUrl;
        $rows[$rowNum]['civicrm_activity_activity_date_time_hover'] = $onHoverAct;
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_activity_activity_date_time', $row) && array_key_exists('civicrm_activity_status_id', $row)) {
        if (CRM_Utils_Date::overdue($rows[$rowNum]['civicrm_activity_activity_date_time']) &&
            $activityStatus[$row['civicrm_activity_status_id']] != 'Completed'
        ) {
          $rows[$rowNum]['class'] = "status-overdue";
          $entryFound = TRUE;
        }
      }

      $entryFound = $this->alterDisplayAddressFields($row, $rows, $rowNum, 'activity', 'List all activities for this ') ? TRUE : $entryFound;

      if (!$entryFound) {
        break;
      }
    }
  }

}
