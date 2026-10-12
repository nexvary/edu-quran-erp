<?php
/**
 * QuranHifz module for Gibbon. SPDX-License-Identifier: GPL-3.0-or-later.
 */
$name = 'QuranHifz';
$description = 'إدارة حلقات تحفيظ القرآن الكريم والتسميع والمراجعة والحضور';
$entryURL = 'index.php';
$type = 'Additional';
$category = 'Learn';
$version = '1.0.00';
$author = 'Edu Quran ERP Contributors';
$url = 'https://github.com/nexvary/edu-quran-erp';

$moduleTables = [];
$moduleTables[] = "CREATE TABLE qhCircle (
  qhCircleID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  teacherPersonID INT UNSIGNED NOT NULL,
  status ENUM('Active','Archived') NOT NULL DEFAULT 'Active',
  createdAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (teacherPersonID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$moduleTables[] = "CREATE TABLE qhEnrollment (
  qhEnrollmentID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  circleID INT UNSIGNED NOT NULL,
  studentPersonID INT UNSIGNED NOT NULL,
  status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  enrolledAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniqueCircleStudent (circleID, studentPersonID),
  INDEX (studentPersonID),
  FOREIGN KEY (circleID) REFERENCES qhCircle(qhCircleID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$moduleTables[] = "CREATE TABLE qhMeeting (
  qhMeetingID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  circleID INT UNSIGNED NOT NULL,
  meetingDate DATE NOT NULL,
  topic VARCHAR(160) NOT NULL DEFAULT '',
  UNIQUE KEY uniqueCircleDate (circleID, meetingDate),
  FOREIGN KEY (circleID) REFERENCES qhCircle(qhCircleID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$moduleTables[] = "CREATE TABLE qhAttendance (
  qhAttendanceID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  meetingID INT UNSIGNED NOT NULL,
  studentPersonID INT UNSIGNED NOT NULL,
  status ENUM('present','absent','excused') NOT NULL,
  UNIQUE KEY uniqueMeetingStudent (meetingID, studentPersonID),
  FOREIGN KEY (meetingID) REFERENCES qhMeeting(qhMeetingID),
  INDEX (studentPersonID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$moduleTables[] = "CREATE TABLE qhRecitation (
  qhRecitationID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  meetingID INT UNSIGNED NOT NULL,
  studentPersonID INT UNSIGNED NOT NULL,
  recitationType ENUM('new','review') NOT NULL,
  surahName VARCHAR(80) NOT NULL,
  verseFrom SMALLINT UNSIGNED NOT NULL,
  verseTo SMALLINT UNSIGNED NOT NULL,
  score TINYINT UNSIGNED NOT NULL,
  remarks VARCHAR(500) NOT NULL DEFAULT '',
  createdBy INT UNSIGNED NOT NULL,
  createdAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (meetingID) REFERENCES qhMeeting(qhMeetingID),
  INDEX (studentPersonID),
  INDEX (createdBy)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$gibbonSetting = [];
$hooks = [];

foreach ([
    ['name' => 'Quran Dashboard', 'file' => 'index.php', 'description' => 'إحصاءات عامة لدار التحفيظ'],
    ['name' => 'Circles and Enrollment', 'file' => 'circles.php', 'description' => 'الحلقات وإلحاق الطلاب'],
    ['name' => 'Meetings', 'file' => 'meetings.php', 'description' => 'إدارة جلسات الحلقات'],
    ['name' => 'Attendance and Recitation', 'file' => 'meeting.php', 'description' => 'الحضور والتسميع والمراجعة'],
    ['name' => 'Quran Reports', 'file' => 'report.php', 'description' => 'تقارير تحصيل الطلاب'],
] as $i => $item) {
    $actionRows[] = [
        'name' => $item['name'],
        'precedence' => (string) ($i * 10),
        'category' => '',
        'description' => $item['description'],
        'URLList' => $item['file'],
        'entryURL' => $item['file'],
        'entrySidebar' => 'Y',
        'menuShow' => 'Y',
        'defaultPermissionAdmin' => 'Y',
        'defaultPermissionTeacher' => 'N',
        'defaultPermissionStudent' => 'N',
        'defaultPermissionParent' => 'N',
        'defaultPermissionSupport' => 'N',
        'categoryPermissionStaff' => 'Y',
        'categoryPermissionStudent' => 'N',
        'categoryPermissionParent' => 'N',
        'categoryPermissionOther' => 'N',
    ];
}
