<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NotificationTemplate;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            ['name' => 'appointment_reminder', 'title' => 'Appointment Reminder', 'sms_template' => 'Dear {{patient_name}}, your appointment with Dr. {{doctor_name}} is scheduled on {{date}} at {{time}}. - {{hospital_name}}'],
            ['name' => 'appointment_confirmed', 'title' => 'Appointment Confirmed', 'sms_template' => 'Dear {{patient_name}}, your appointment has been confirmed for {{date}} at {{time}}. Token: {{token}}. - {{hospital_name}}'],
            ['name' => 'lab_results_ready', 'title' => 'Lab Results Ready', 'sms_template' => 'Dear {{patient_name}}, your lab results (Order #{{order_no}}) are ready. Please visit the hospital. - {{hospital_name}}'],
            ['name' => 'prescription_ready', 'title' => 'Prescription Ready', 'sms_template' => 'Dear {{patient_name}}, your prescription is ready for collection from the pharmacy. - {{hospital_name}}'],
            ['name' => 'bill_generated', 'title' => 'Bill Generated', 'sms_template' => 'Dear {{patient_name}}, your bill of {{currency}} {{amount}} has been generated (Invoice #{{invoice_no}}). - {{hospital_name}}'],
            ['name' => 'discharge_summary', 'title' => 'Discharge Summary', 'sms_template' => 'Dear {{patient_name}}, you have been discharged. Thank you for choosing {{hospital_name}}. Take care!'],
            ['name' => 'payment_received', 'title' => 'Payment Received', 'sms_template' => 'Dear {{patient_name}}, payment of {{currency}} {{amount}} received. Receipt #{{receipt_no}}. - {{hospital_name}}'],
            ['name' => 'subscription_expiry', 'title' => 'Subscription Expiry', 'sms_template' => 'HMS subscription for {{hospital_name}} expires on {{expiry_date}}. Please renew to continue. - Trenexa IT Solutions'],
            // Support Centre — see SUPER_ADMIN_SUPPORT_CENTRE_REPORT.md.
            ['name' => 'ticket_reply', 'title' => 'Support Ticket Reply', 'sms_template' => 'Update on your support ticket {{ticket_number}} ({{subject}}): {{reply}}'],
        ];

        foreach ($templates as $template) {
            NotificationTemplate::updateOrCreate(
                ['name' => $template['name']],
                array_merge($template, [
                    'whatsapp_template' => $template['sms_template'],
                    'email_template'    => $template['sms_template'],
                    'is_active'         => true,
                ])
            );
        }
    }
}
