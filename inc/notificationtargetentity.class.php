<?php

class PluginCreditNotificationTargetEntity extends NotificationTarget {

   function getEvents() {
      return [
          'expired' => __('Expiration date')
      ];
   }

   public function addDataForTemplate($event, $options = []) {
      global $CFG_GLPI, $DB;

      $this->data['##credit.voucher##'] = $this->obj->getField('name');
      $this->data['##credit.quantity##'] = $this->obj->getField('quantity');
      $this->data['##credit.enddate##'] = $this->obj->getField('end_date');

      $query='SELECT SUM(`glpi_plugin_credit_tickets`.`consumed`) AS `consumed_total`
              FROM `glpi_plugin_credit_tickets` 
              WHERE `glpi_plugin_credit_tickets`.`plugin_credit_entities_id` = '.$this->obj->getField('id');
      $result = $DB->query($query);
      $data = $DB->fetch_assoc($result);
      $this->data['##credit.left##']=(int)$this->obj->getField('quantity')-(int)$data['consumed_total'];

      $this->data['##lang.credit.enddate##']=__('End date');
      $this->data['##lang.credit.left##']=__('Quantity remaining', 'credit');
      $this->data['##lang.credit.quantity##']=__('Quantity sold', 'credit');
      $this->data['##lang.credit.voucher##']=__('Credit voucher', 'credit');
   }

   public function getTags() {
      $tags=[
         'credit.voucher' => __('Credit voucher', 'credit'),
         'credit.quantity' => __('Quantity sold', 'credit'),
         'credit.enddate' => __('End date'),
         'credit.left'    => __('Quantity remaining', 'credit'),
      ];

      foreach ($tags as $tag => $label) {
         $this->addTagToList([
            'tag'   => $tag,
            'label' => $label,
            'value' => true,
         ]);
      }

      asort($this->tag_descriptions);
   }


   public static function install(Migration $migration) {
      global $DB;

      $migration->displayMessage("Migrate PluginCreditEntity notifications");

      $template     = new NotificationTemplate();
      $translation  = new NotificationTemplateTranslation();
      $notification = new Notification();
      $n_n_template = new Notification_NotificationTemplate();

      $templates_id = false;
      $query_id     = "SELECT `id`
                       FROM `glpi_notificationtemplates`
                       WHERE `itemtype`='PluginCreditEntity'
                       AND `name` = 'Credit expired'";
      $result       = $DB->query($query_id) or die ($DB->error());

      if ($DB->numrows($result) > 0) {
         $templates_id = $DB->result($result, 0, 'id');
      } else {
         $tmp = [
            'name'     => 'Credit expired',
            'itemtype' => 'PluginCreditEntity',
            'date_mod' => $_SESSION['glpi_currenttime'],
            'comment'  => '',
            'css'      => '',
         ];
         $templates_id = $template->add($tmp);
      }

      if ($templates_id) {
         if (!countElementsInTable($translation->getTable(), ['notificationtemplates_id' => $templates_id])) {

            $contentText = 'The credit ##credit.voucher## will expire soon.';
            $contentText.= 'Please, consider renew it quickly.';

            $contentHtml = '<p>The credit <strong>##credit.voucher##</strong> will expire soon.</p>';
            $contentHtml.= '<p>Please, consider renew it quickly.</p>';

            $tmp = [];
            $tmp['notificationtemplates_id'] = $templates_id;
            $tmp['language']                 = '';
            $tmp['subject']                  = '##lang.credit.voucher##';
            $tmp['content_text']             = $contentText;
            $tmp['content_html']             = $contentHtml;
            $translation->add($tmp);
         }

         $notifs=['Credit expired'=>'expired'];
         foreach ($notifs as $label => $name) {
            if (!countElementsInTable("glpi_notifications", ['itemtype'=>'PluginCreditEntity','event'=>$name])) {
               $notification_id = $notification->add([
                  'name'                     => $label,
                  'entities_id'              => 0,
                  'itemtype'                 => 'PluginCreditEntity',
                  'event'                    => $name,
                  'comment'                  => '',
                  'is_recursive'             => 1,
                  'is_active'                => 1,
                  'date_mod'                 => $_SESSION['glpi_currenttime'],
               ]);

               $n_n_template->add([
                  'notifications_id'         => $notification_id,
                  'mode'                     => Notification_NotificationTemplate::MODE_MAIL,
                  'notificationtemplates_id' => $templates_id,
               ]);
            }
         }
      }
   }


   public static function uninstall() {
      global $DB;

      $notif = new Notification();

      foreach (['expired'] as $event) {
         $options = [
            'itemtype' => 'PluginCreditEntity',
            'event'    => $event,
            'FIELDS'   => 'id',
         ];
         foreach ($DB->request('glpi_notifications', $options) as $data) {
            $notif->delete($data);
         }
      }

      //templates
      $template    = new NotificationTemplate();
      $translation = new NotificationTemplateTranslation();
      $options     = [
         'itemtype' => 'PluginCreditEntity',
         'FIELDS'   => 'id'
      ];

      foreach ($DB->request('glpi_notificationtemplates', $options) as $data) {
         $options_template = [
            'notificationtemplates_id' => $data['id'],
            'FIELDS'                   => 'id'
         ];
         foreach ($DB->request('glpi_notificationtemplatetranslations', $options_template) as $data_template) {
            $translation->delete($data_template);
         }
         $template->delete($data);
      }
   }


}