# المعمارية والأمان — v0.1

## اختيار القاعدة

يعتمد المشروع على [Gibbon](https://github.com/GibbonEdu/core) كمنصة إدارة مدرسية قائمة بذاتها. ترخيصه GPL-3.0. لا ننسخ إلى المستودع الحالي ملفات Gibbon؛ تُجلب في `core/` بشكل مستقل عند التشغيل. وحدة `modules/QuranHifz/` تطوير مخصص وفق [دليل بناء الإضافات](https://docs.gibbonedu.org/explanation/development/module-development).

## حدود المسؤولية

| وحدة | الجهة المالكة للبيانات | الوضع الحالي |
| --- | --- | --- |
| الطلاب والأسر والعاملون والسنة الدراسية | Gibbon | جاهز في المصدر upstream، يحتاج اختبار تكامل محلي |
| حلقات التحفيظ | qhCircle | CRUD: إنشاء/عرض فقط |
| انتساب الطلاب للحلقات | qhEnrollment | إضافة/إعادة تفعيل/عرض أعداد فقط |
| الجلسات | qhMeeting | إنشاء/عرض فقط |
| الحضور | qhAttendance | حفظ وتحديث الحالة، عرض في التقرير الإجمالي |
| الحفظ والمراجعة | qhRecitation | إضافة وعرض وتقرير متوسط الدرجات |
| الميزانيات والبوابة والرسائل | غير منفذ | مراحل مستقبلية |

## مفاتيح الربط

- `qhCircle.teacherPersonID`, `qhEnrollment.studentPersonID`, `qhRecitation.createdBy` = `gibbonPerson.gibbonPersonID` (من دون قيود FK عبر الجداول الأساسية، للحفاظ على استقلال التطوير).
- `qhMeeting.circleID` و`qhEnrollment.circleID` = `qhCircle.qhCircleID`.
- `qhRecitation.meetingID` و`qhAttendance.meetingID` = `qhMeeting.qhMeetingID`.
- جداول QuranHifz تستخدم InnoDB وutf8mb4 ومفاتيح فريدة حيث يلزم.

## القيود الأمنية الحالية

- افتراضيًا، جميع صفحات وحدة التحفيظ متاحة لدور المدير فقط؛ وصول المعلمين وأولياء الأمور مؤجل حتى تطبيق عزل الحلقات وصلاحيات مستوى السجل.
- تستخدم معالجة POST استعلامات مجهزة مع CSRF، تحقق السيرفر من القيم، وتحويل HTTP 303 بعد العملية.
- تعديلات الحضور لا تضيف سجلًا مكررًا لنفس الطالب والجلسة.
- صفحة التقارير تحسب الحضور المسجّل فقط؛ الجلسات غير المسجلة لا تُفسر تلقائيًا كغياب.
- لم تُنفذ بعد: مراقبة التدقيق، آليات الحذف، اختبار الاختراق، ضبط تصدير البيانات، حماية مؤسسات متعددة، ملفات مرفوعة.
- **لا تُدخل بيانات الطلاب الحقيقية أو بيانات أولياء الأمور قبل الاختبارات الأمنية ومراجعة النشر والإجراءات القانونية المحلية.**

## بنية الكود

```text
edu-quran-erp/
├── modules/QuranHifz/
│   ├── manifest.php       # Gibbon module and MySQL schema
│   ├── moduleFunctions.php # authorization/CSRF/helpers
│   ├── validation.php      # pure validation
│   ├── index.php           # dashboard
│   ├── circles.php         # circles/enrollment
│   ├── meetings.php        # meeting list/create
│   ├── meeting.php         # attendance/recitation forms
│   ├── save.php            # POST handlers
│   └── report.php          # summaries
├── scripts/install-local.sh
├── tests/validation_test.php
├── .github/workflows/php-checks.yml
└── docs/
```

## متطلبات قبل اعتبار MVP جاهزًا

1. تشغيل Gibbon مع قاعدة MySQL في بيئة تطوير مستقلة.
2. تثبيت QuranHifz من لوحة إدارة الوحدات وتأكيد أن `manifest.php` ينشئ الجداول والصلاحيات.
3. تجربة إضافة معلم وطالب وحلقة وتسميع فعلي، وفحص ظهور النتائج في التقرير.
4. اختبار صلاحيات admin مقابل teacher/student/parent وإغلاق الصفحات غير المخوّلة.
5. خطة النسخ الاحتياطي والاستعادة، ضبط التوقيت، والفترة الدراسية.

## أمثلة الوظائف التجارية

لا تُستخدم شفرات أو صور أو أصول InfixEdu/Smart School داخل المستودع. المستهدف الاستفادة من متطلبات ERP الشائعة كالإدارة الأكاديمية والمالية والبوابات والرسائل، وإعادة تنفيذها أصلًا حسب الحاجة.
