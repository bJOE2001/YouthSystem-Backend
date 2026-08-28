<?php

namespace App\Models;

/**
 * Backward compatibility alias for AttendanceLog.
 */
class EventAttendance extends AttendanceLog
{
    protected $table = 'attendance_logs';
}
