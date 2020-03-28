<?php

  class ent_attribute_group {
    public $data;
    public $previous;

    public function __construct($group_id=null) {

      if ($group_id !== null) {
        $this->load($group_id);
      } else {
        $this->reset();
      }
    }

    public function reset() {

      $this->data = array();

      $fields_query = database::query(
        "show fields from ". DB_TABLE_ATTRIBUTE_GROUPS .";"
      );

      while ($field = database::fetch($fields_query)) {
        $this->data[$field['Field']] = null;
      }

      $info_fields_query = database::query(
        "show fields from ". DB_TABLE_ATTRIBUTE_GROUPS_INFO .";"
      );

      while ($field = database::fetch($info_fields_query)) {
        if (in_array($field['Field'], array('id', 'group_id', 'language_code'))) continue;

        $this->data[$field['Field']] = array();
        foreach (array_keys(language::$languages) as $language_code) {
          $this->data[$field['Field']][$language_code] = null;
        }
      }

      $this->data['values'] = array();

      $this->previous = $this->data;
    }

    public function load($group_id) {

      if (!preg_match('#^[0-9]+$#', $group_id)) throw new Exception('Invalid attribute (ID: '. $group_id .')');

      $this->reset();

      $group_query = database::query(
        "select * from ". DB_TABLE_ATTRIBUTE_GROUPS ."
        where id = ". (int)$group_id ."
        limit 1;"
      );

      if ($group = database::fetch($group_query)) {
        $this->data = array_replace($this->data, array_intersect_key($group, $this->data));
      } else {
        throw new Exception('Could not find attribute (ID: '. (int)$group_id .') in database.');
      }

      $group_info_query = database::query(
        "select name, language_code from ". DB_TABLE_ATTRIBUTE_GROUPS_INFO ."
        where group_id = ". (int)$group_id .";"
      );

      while ($group = database::fetch($group_info_query)) {
        $this->data['name'][$group['language_code']] = $group['name'];
      }

      $values_query = database::query(
        "select * from ". DB_TABLE_ATTRIBUTE_VALUES ."
        where group_id = ". (int)$group_id ."
        order by priority;"
      );

      while ($value = database::fetch($values_query)) {
        $this->data['values'][$value['id']] = $value;

        $values_info_query = database::query(
          "select * from ". DB_TABLE_ATTRIBUTE_VALUES_INFO ."
          where value_id = ". (int)$value['id'] .";"
        );

        while ($value_info = database::fetch($values_info_query)) {
          foreach (array_keys($value_info) as $key) {
            if (in_array($key, array('id', 'value_id', 'language_code'))) continue;
            $this->data['values'][$value['id']][$key][$value_info['language_code']] = $value_info[$key];
          }
        }
      }

      $this->previous = $this->data;
    }

    public function save() {

    // Group
      if (empty($this->data['id'])) {
        database::query(
          "insert into ". DB_TABLE_ATTRIBUTE_GROUPS ."
          (date_created)
          values ('". ($this->data['date_created'] = date('Y-m-d H:i:s')) ."');"
        );
        $this->data['id'] = database::insert_id();
      }

      database::query(
        "update ". DB_TABLE_ATTRIBUTE_GROUPS ." set
          code = '". database::input($this->data['code']) ."',
          date_updated = '". ($this->data['date_updated'] = date('Y-m-d H:i:s')) ."'
        where id = ". (int)$this->data['id'] ."
        limit 1;"
      );

    // Group info
      foreach (array_keys(language::$languages) as $language_code) {

        $group_info_query = database::query(
          "select id from ". DB_TABLE_ATTRIBUTE_GROUPS_INFO ."
          where group_id = ". (int)$this->data['id'] ."
          and language_code = '". database::input($language_code) ."'
          limit 1;"
        );

        if (!$group_info = database::fetch($group_info_query)) {
          database::query(
            "insert into ". DB_TABLE_ATTRIBUTE_GROUPS_INFO ."
            (group_id, language_code)
            values (". (int)$this->data['id'] .", '". database::input($language_code) ."');"
          );
          $group_info['id'] = database::insert_id();
        }

        database::query(
          "update ". DB_TABLE_ATTRIBUTE_GROUPS_INFO ."
          set name = '". database::input($this->data['name'][$language_code]) ."'
          where id = ". (int)$group_info['id'] ."
          and group_id = ". (int)$this->data['id'] ."
          and language_code = '". database::input($language_code) ."'
          limit 1;"
        );
      }

    // Delete values
      $values_query = database::query(
        "select id from ". DB_TABLE_ATTRIBUTE_VALUES ."
        where group_id = ". (int)$this->data['id'] ."
        and id not in ('". @implode("', '", array_column($this->data['values'], 'id')) ."');"
      );

      while ($value = database::fetch($values_query)) {

        $products_attributes_query = database::query(
          "select id from ". DB_TABLE_PRODUCTS_ATTRIBUTES ."
          where value_id = ". (int)$value['id'] ."
          limit 1;"
        );

        if (database::num_rows($products_attributes_query)) throw new Exception('Cannot delete value linked to product attributes');

        $products_options_query = database::query(
          "select id from ". DB_TABLE_PRODUCTS_OPTIONS_VALUES ."
          where value_id = ". (int)$value['id'] ."
          limit 1;"
        );

        if (database::num_rows($products_options_query)) throw new Exception('Cannot delete value linked to product options');

        database::query(
          "delete from ". DB_TABLE_ATTRIBUTE_VALUES ."
          where group_id = ". (int)$this->data['id'] ."
          and id = ". (int)$value['id'] ."
          limit 1;"
        );

        database::query(
          "delete from ". DB_TABLE_ATTRIBUTE_VALUES_INFO ."
          where value_id = ". (int)$value['id'] .";"
        );
      }

    // Update/Insert values
      $i = 0;
      foreach ($this->data['values'] as $value) {

        if (empty($value['id'])) {
          database::query(
            "insert into ". DB_TABLE_ATTRIBUTE_VALUES ."
            (group_id, date_created)
            values (". (int)$this->data['id'] .", '". date('Y-m-d H:i:s') ."');"
          );
          $value['id'] = database::insert_id();
        }

        database::query(
          "update ". DB_TABLE_ATTRIBUTE_VALUES ." set
            priority = ". (int)$i++ .",
            date_updated = '". ($this->data['date_updated'] = date('Y-m-d H:i:s')) ."'
          where id = ". (int)$value['id'] ."
          limit 1;"
        );

        foreach (array_keys(language::$languages) as $language_code) {

          $value_info_query = database::query(
            "select id from ". DB_TABLE_ATTRIBUTE_VALUES_INFO ."
            where value_id = ". (int)$value['id'] ."
            and language_code = '". database::input($language_code) ."'
            limit 1;"
          );

          if (!$value_info = database::fetch($value_info_query)) {
            database::query(
              "insert into ". DB_TABLE_ATTRIBUTE_VALUES_INFO ."
              (value_id, language_code)
              values ('". $value['id'] ."', '". database::input($language_code) ."');"
            );
            $value_info['id'] = database::insert_id();
          }

          database::query(
            "update ". DB_TABLE_ATTRIBUTE_VALUES_INFO ."
            set name = '". database::input($value['name'][$language_code]) ."'
            where id = ". (int)$value_info['id'] ."
            and value_id = ". (int)$value['id'] ."
            and language_code = '". database::input($language_code) ."'
            limit 1;"
          );
        }
      }

      $this->previous = $this->data;

      cache::clear_cache('attributes');
    }

    public function delete() {

      if (empty($this->data['id'])) return;

    // Check products for attribute
      $products_attributes_query = database::query(
        "select id from ". DB_TABLE_PRODUCTS_ATTRIBUTES ."
        where group_id = ". (int)$this->data['id'] .";"
      );

      if (database::num_rows($products_attributes_query)) throw new Exception('Cannot delete group linked to products');

    // Check products for options
      $products_options_query = database::query(
        "select id from ". DB_TABLE_PRODUCTS_ATTRIBUTES ."
        where group_id = ". (int)$this->data['id'] .";"
      );

      if (database::num_rows($products_options_query)) throw new Exception('Cannot delete group linked to products');

      $this->data['values'] = array();
      $this->save();

    // Delete attribute
      database::query(
        "delete from ". DB_TABLE_ATTRIBUTE_GROUPS ."
        where id = ". (int)$this->data['id'] ."
        limit 1;"
      );

      database::query(
        "delete from ". DB_TABLE_ATTRIBUTE_GROUPS_INFO ."
        where group_id = ". (int)$this->data['id'] .";"
      );

      $this->reset();

      cache::clear_cache('attributes');
    }
  }
