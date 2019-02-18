<section id="box-checkout-payment" class="box">
  <h2 class="title"><?php echo language::translate('title_payment', 'Payment'); ?></h2>

  <div class="options btn-group-vertical">

    <?php foreach ($options as $module) foreach ($module['options'] as $option) { ?>
    <label class="option btn btn-default btn-block<?php echo (!empty($selected['id']) && $selected['id'] == $module['id'].':'.$option['id']) ? ' active' : ''; ?><?php echo !empty($option['error']) ? ' disabled' : ''; ?>">
      <?php echo functions::form_draw_radio_button('payment[option_id]', $module['id'].':'.$option['id'], !empty($selected['id']) ? $selected['id'] : '', 'style="display: none;"' . (!empty($option['error']) ? ' disabled="disabled"' : '')); ?>
      <div class="header row" style="margin: 0;">
        <div class="col-sm-3 thumbnail" style="margin: 0;">
          <img src="<?php echo document::href_link(WS_DIR_HTTP_HOME . functions::image_thumbnail(FS_DIR_HTTP_ROOT . WS_DIR_HTTP_HOME . $option['icon'], 140, 60, 'FIT_ONLY_BIGGER_USE_WHITESPACING')); ?>" />
        </div>

        <div class="col-sm-9 text-left" style="padding-bottom: 0;">
          <div class="title"><?php echo $module['title']; ?></div>
          <div class="name"><?php echo $option['name']; ?></div>
          <div class="price"><?php echo (empty($option['error']) && $option['cost'] != 0) ? '+ ' . currency::format(tax::get_price($option['cost'], $option['tax_class_id'])) : language::translate('text_no_fee', 'No fee'); ?></div>
          <?php if (!empty($option['error'])) { ?><div class="error"><?php echo $option['error']; ?></div><?php } ?>
        </div>
      </div>

      <?php if (empty($option['error']) && (!empty($option['description']) || !empty($option['fields']))) { ?>
      <div class="content">
        <hr />
        <?php if (!empty($option['description'])) { ?><p class="description text-left"><?php echo $option['description']; ?></p><?php } ?>
        <?php if (!empty($option['fields'])) { ?><div class="fields text-left"><?php echo $option['fields']; ?></div><?php } ?>
      </div>
      <?php } ?>
    </label>
    <?php } ?>

  </div>
</section>

<script>
  $('#box-checkout-payment .option.active :input').prop('disabled', false);
  $('#box-checkout-payment .option:not(.active) :input').prop('disabled', true);
</script>