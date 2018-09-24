<?php

class PluginCreditNotificationTargetEntity extends NotificationTarget {

   function getEvents() {
      return [
          'expired' => __('Expiration date')
      ];
   }

   public function addDataForTemplate($event, $options = []) {
      global $DB;

      $this->data['##credit.name##'] = $this->obj->getField('name');
      $this->data['##credit.quantity_sold##'] = $this->obj->getField('quantity');
      $this->data['##credit.enddate##'] = $this->obj->getField('end_date');

      $req=$DB->request(['SELECT'=>['SUM'=>'consumed AS consumed_total'], 'FROM'=>'glpi_plugin_credit_tickets','WHERE'=>['plugin_credit_entities_id'=>$this->obj->getField('id')]]);
      $data=$req->next();
      $this->data['##credit.quantity_remaining##']=(int)$this->obj->getField('quantity')-(int)$data['consumed_total'];

      $this->data['##lang.credit.enddate##']=__('End date');
      $this->data['##lang.credit.quantity_remaining##']=__('Quantity remaining', 'credit');
      $this->data['##lang.credit.quantity_sold##']=__('Quantity sold', 'credit');
      $this->data['##lang.credit.name##']=__('Credit voucher', 'credit');

      $this->getTags();
      foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
         if (!isset($this->data[$tag])) {
            $this->data[$tag] = $values['label'];
         }
      }
   }

   public function getTags() {
      $tags=[
         'credit.name'                 => __('Credit voucher', 'credit'),
         'credit.quantity_sold'        => __('Quantity sold', 'credit'),
         'credit.enddate'              => __('End date'),
         'credit.quantity_remaining'   => __('Quantity remaining', 'credit'),
      ];

      foreach ($tags as $tag => $label) {
         $this->addTagToList([
            'tag'   => $tag,
            'label' => $label,
            'value' => true,
         ]);
      }

      $lang=['credit.expired'=>__("will expire soon"),
            'credit.expired.information'=>__("This will expire soon.\nPlease, consider buying a new one")
      ];

      foreach ($lang as $tag => $label) {
         $this->addTagToList(['tag'   => $tag,
                              'label' => $label,
                              'value' => false,
                              'lang'  => true]);
      }

      asort($this->tag_descriptions);
      return $this->tag_descriptions;
   }


   public static function install(Migration $migration) {
      global $DB;

      $migration->displayMessage("Migrate PluginCreditEntity notifications");

      $template     = new NotificationTemplate();
      $translation  = new NotificationTemplateTranslation();
      $notification = new Notification();
      $n_n_template = new Notification_NotificationTemplate();

      $templates_id = false;
      $result=$DB->request(['SELECT'=>'id', 'FROM'=>'glpi_notificationtemplates','WHERE'=>['itemtype'=>'PluginCreditEntity','name'=>'Credit expired']]);

      if (count($result) > 0) {
         $data=$result->next();
         $templates_id =$data['id'];
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

            $tmp = [];
            $tmp['notificationtemplates_id'] = $templates_id;
            $tmp['language']                 = '';
            $tmp['subject']                  = '##credit.name## ##lang.credit.expired##';
            $tmp['content_text']             = '##lang.credit.expired.information##';
            $tmp['content_html']             = '##lang.credit.expired.information##';
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