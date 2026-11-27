<?php
namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RsatSchedule
{
    public static function rows(string $json): array
    {
        try { $rows=json_decode($json,true,512,JSON_THROW_ON_ERROR); }
        catch (\JsonException $e) { throw ValidationException::withMessages(['rowsJson'=>'Invalid RSAT activity data.']); }
        Validator::make(['rows'=>$rows],[
            'rows'=>'required|array|min:1|max:200','rows.*.id'=>'required|string|max:100|distinct',
            'rows.*.service'=>'required|string|max:255','rows.*.activity'=>'required|string|max:2000',
            'rows.*.schedules'=>'required|array|min:1|max:24',
            'rows.*.schedules.*.version'=>'required|integer|in:1',
            'rows.*.schedules.*.configured'=>'required|accepted',
            'rows.*.schedules.*.frequency.unit'=>'required|in:day,week,month,quarter,year,event',
            'rows.*.schedules.*.frequency.interval'=>'required|integer|between:1,99',
            'rows.*.schedules.*.anchor'=>'required|date_format:Y-m-d',
            'rows.*.schedules.*.deadline.type'=>'required|in:day,nth_weekday,period_end,event,interval,weekday',
            'rows.*.schedules.*.deadline.day'=>'required|integer|between:1,31',
            'rows.*.schedules.*.deadline.monthInPeriod'=>'required|integer|between:1,12',
            'rows.*.schedules.*.deadline.ordinal'=>'required|integer|in:-1,1,2,3,4,5',
            'rows.*.schedules.*.deadline.weekday'=>'required|integer|between:0,6',
            'rows.*.schedules.*.deadline.offsetDays'=>'required|integer|between:0,366',
            'rows.*.schedules.*.deadline.event'=>'required|string|max:255',
            'rows.*.schedules.*.deadline.eventDate'=>'nullable|date_format:Y-m-d',
            'rows.*.schedules.*.reminder.amount'=>'required|integer|between:0,366',
            'rows.*.schedules.*.reminder.unit'=>'required|in:calendar_days,business_days',
            'rows.*.schedules.*.shortMonth'=>'required|in:last_day,skip',
            'rows.*.schedules.*.adjustment'=>'required|in:none,next,previous',
            'rows.*.schedules.*.holidays'=>'present|array|max:730',
            'rows.*.schedules.*.holidays.*'=>'date_format:Y-m-d',
            'rows.*.schedules.*.policyReference'=>'nullable|string|max:2000',
        ])->validate();
        foreach($rows as $row)foreach($row['schedules'] as $r){
            $unit=$r['frequency']['unit'];$type=$r['deadline']['type'];
            $allowed=match($unit){'day'=>['interval'],'week'=>['weekday'],'event'=>['event'],default=>['day','nth_weekday','period_end','event']};
            $max=match($unit){'quarter'=>3,'year'=>12,default=>1};
            if(!in_array($type,$allowed,true)||in_array($type,['day','nth_weekday'])&&$r['deadline']['monthInPeriod']>$max)throw ValidationException::withMessages(['rowsJson'=>'The recurring deadline does not match its frequency.']);
        }
        return $rows;
    }
}
