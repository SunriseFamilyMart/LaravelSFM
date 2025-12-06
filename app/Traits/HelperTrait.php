<?php

namespace App\Traits;

use App\CentralLogics\Helpers;
use App\Model\BusinessSetting;
use Illuminate\Support\Carbon;

trait HelperTrait
{
    public function translate_message($language_code, $message_key)
    {
        $message = '';
        $statusKey = Helpers::order_status_message_key($message_key);
        $translatedMessage = BusinessSetting::with('translations')->where(['key' => $statusKey])->first();
        if (isset($translatedMessage->translations)){
            foreach ($translatedMessage->translations as $translation){
                if ($language_code == $translation->locale){
                    $message = $translation->value;
                }
            }
        }
        return $message;
    }

    public function dynamic_key_replaced_message($message, $type, $order = null, $customer = null)
    {
        $customerName = '';
        $deliverymanName = '';
        $order_id = $order ? $order->id : '';

        if ($type == 'order'){
            $deliverymanName = $order->delivery_man ? $order->delivery_man->f_name. ' '. $order->delivery_man->l_name : '';
            $customerName = $order->is_guest == 0 ? ($order->customer ? $order->customer->f_name. ' '. $order->customer->l_name : '') : 'Guest User';
        }
        if ($type == 'wallet'){
            $customerName = $customer->f_name. ' '. $customer->l_name;
        }
        $storeName = Helpers::get_business_settings('restaurant_name');
        $value = Helpers::text_variable_data_format(value:$message, user_name: $customerName, store_name: $storeName, delivery_man_name: $deliverymanName, order_id: $order_id);
        return $value;
    }

    public function checkMaintenanceMode(): array
    {
        $maintenanceSystemArray = ['branch_panel', 'customer_app', 'web_app', 'deliveryman_app', 'table_app', 'kitchen_app'];
        $selectedMaintenanceSystem = Helpers::get_business_settings('maintenance_system_setup')?? [];

        $maintenanceSystem = [];
        foreach ($maintenanceSystemArray as $system) {
            $maintenanceSystem[$system] = in_array($system, $selectedMaintenanceSystem) ? 1 : 0;
        }

        $selectedMaintenanceDuration = Helpers::get_business_settings('maintenance_duration_setup') ?? [];
        $maintenanceStatus = (integer)(Helpers::get_business_settings('maintenance_mode') ?? 0);

        $status = 0;
        if ($maintenanceStatus == 1) {
            if (isset($selectedMaintenanceDuration['maintenance_duration']) && $selectedMaintenanceDuration['maintenance_duration'] == 'until_change') {
                $status = $maintenanceStatus;
            } else {
                if (isset($selectedMaintenanceDuration['start_date']) && isset($selectedMaintenanceDuration['end_date'])) {
                    $start = \Carbon\Carbon::parse($selectedMaintenanceDuration['start_date']);
                    $end = Carbon::parse($selectedMaintenanceDuration['end_date']);
                    $today = Carbon::now();
                    if ($today->between($start, $end)) {
                        $status = 1;
                    }
                }
            }
        }

        return [
            'maintenance_status' => $status,
            'selected_maintenance_system' => $maintenanceSystem,
            'maintenance_messages' => Helpers::get_business_settings('maintenance_message_setup') ?? [],
            'maintenance_type_and_duration' => $selectedMaintenanceDuration,
        ];
    }



}
