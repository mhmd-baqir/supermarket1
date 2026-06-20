-- Google OAuth: إضافة عمود google_id لجدول المستخدمين
ALTER TABLE users ADD COLUMN google_id VARCHAR(255) NULL UNIQUE AFTER email;

-- جعل حقل كلمة المرور يقبل القيمة الفارغة (مستخدمو Google لا يحتاجون كلمة مرور)
ALTER TABLE users MODIFY password VARCHAR(255) NULL;
