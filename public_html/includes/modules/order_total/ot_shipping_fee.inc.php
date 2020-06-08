<?php

  class ot_shipping_fee {
    public $id = __CLASS__;
    public $name = 'Shipping Fee';
    public $description = '';
    public $author = 'LiteCart Dev Team';
    public $version = '1.0';
    public $website = 'https://www.litecart.net';
    public $priority = 0;

    public function __construct() {
      $this->name = language::translate(__CLASS__.':title', 'Shipping Fee');
    }

    public function process($order) {

      if (empty($this->settings['status'])) return;

      if (!isset($order->data['shipping_option']['cost']) || $order->data['shipping_option']['cost'] == 0) return;

      $output = array();

      $output[] = array(
        'title' => $order->data['shipping_option']['title'] .' ('. $order->data['shipping_option']['name'] .')',
        'value' => $order->data['shipping_option']['cost'],
        'tax' => tax::get_tax($order->data['shipping_option']['cost'], $order->data['shipping_option']['tax_class_id'], $order->data['customer']),
        'calculate' => true,
      );

      if (!empty($this->settings['free_shipping_table'])) {

      // Calculate cart total
        $subtotal = 0;
        foreach ($order->data['items'] as $item) {
          $subtotal += $item['quantity'] * $item['price'];
        }

      // Check for free shipping
        if ($free_shipping_table = functions::csv_decode($this->settings['free_shipping_table'], ',')) {

          foreach ($free_shipping_table as $row) {
            if (empty($row['country_code']) || $row['country_code'] != $order->data['customer']['shipping_address']['country_code']) continue;
            if (!empty($row['zone_code']) && $row['zone_code'] != $order->data['customer']['shipping_address']['zone_code']) continue;
            if (!isset($row['min_subtotal']) || $row['min_subtotal'] < 0) continue;
            if ($subtotal < $row['min_subtotal']) continue;

            $output[] = array(
              'title' => language::translate('title_free_shipping', 'Free Shipping'),
              'value' => -$order->data['shipping_option']['cost'],
              'tax' => -tax::get_tax($order->data['shipping_option']['cost'], $order->data['shipping_option']['tax_class_id'], $order->data['customer']),
              'tax_class_id' => $order->data['shipping_option']['tax_class_id'],
              'calculate' => true,
            );

            break;
          }
        }
      }

      return $output;
    }

    function settings() {
      return array(
        array(
          'key' => 'status',
          'default_value' => '1',
          'title' => language::translate(__CLASS__.':title_status', 'Status'),
          'description' => language::translate(__CLASS__.':description_status', 'Enables or disables the module.'),
          'function' => 'toggle("e/d")',
        ),
        array(
          'key' => 'free_shipping_table',
          'default_value' => 'country_code,zone_code,min_subtotal',
          'title' => language::translate(__CLASS__.':title_free_shipping_table', 'Free Shipping Table'),
          'description' => language::translate(__CLASS__.':description_free_shipping_table', 'Free shipping table in standard CSV format with column headers.'),
          'function' => 'csv()',
        ),
        array(
          'key' => 'priority',
          'default_value' => '20',
          'title' => language::translate(__CLASS__.':title_priority', 'Priority'),
          'description' => language::translate(__CLASS__.':description_priority', 'Process this module by the given priority value.'),
          'function' => 'number()',
        ),
      );
    }

    public function install() {}

    public function uninstall() {}
  }
