# 데이터베이스 마이그레이션 가이드 (로컬 → 웹호스팅)

## 📋 사전 준비사항

1. 카페24 호스팅 관리자에서 데이터베이스 생성 완료
2. 데이터베이스 접속 정보 확인:
   - 호스트: 보통 `localhost` 또는 카페24에서 제공하는 호스트
   - 데이터베이스 이름
   - 사용자명
   - 비밀번호
3. phpMyAdmin 접속 가능 (카페24 호스팅 관리자에서 제공)

---

## 🔄 방법 1: phpMyAdmin 사용 (권장)

### 1단계: 로컬 DB에서 데이터 덤프

1. 로컬 phpMyAdmin 접속 (`http://localhost/phpmyadmin`)
2. 왼쪽에서 `dolbom` 데이터베이스 선택
3. 상단 탭에서 **"내보내기"** 클릭
4. 설정:
   - **방법**: 빠른
   - **형식**: SQL
   - **구조와 데이터 모두** 선택
5. **"실행"** 클릭하여 SQL 파일 다운로드

### 2단계: 호스팅 DB에 데이터 임포트

1. 카페24 호스팅 관리자 > 데이터베이스 관리 > phpMyAdmin 접속
2. 왼쪽에서 생성한 데이터베이스 선택
3. 상단 탭에서 **"가져오기"** 클릭
4. **"파일 선택"** 클릭하여 다운로드한 SQL 파일 선택
5. **"실행"** 클릭하여 데이터 임포트

### 3단계: 설정 파일 업데이트

`config/hosting.php` 파일에서 데이터베이스 정보 수정:

```php
define('DB_HOST', 'localhost'); // 카페24 DB 호스트
define('DB_NAME', 'your_database_name'); // 실제 DB 이름
define('DB_USER', 'your_database_user'); // 실제 DB 사용자명
define('DB_PASS', 'your_database_password'); // 실제 DB 비밀번호
```

---

## 🔄 방법 2: 명령줄 사용 (고급)

### 1단계: 로컬에서 덤프 생성

**Windows PowerShell:**
```powershell
cd c:\xampp\mysql\bin
.\mysqldump.exe -u root -p dolbom > C:\backup\dolbom_backup.sql
```

비밀번호 입력 후 덤프 파일 생성됨.

### 2단계: 호스팅 서버에 업로드

1. 생성된 `dolbom_backup.sql` 파일을 FTP로 호스팅 서버에 업로드
2. 또는 phpMyAdmin의 "가져오기" 기능 사용

### 3단계: 호스팅에서 임포트

**SSH 접속이 가능한 경우:**
```bash
mysql -u your_db_user -p your_database_name < dolbom_backup.sql
```

**phpMyAdmin 사용:**
- 방법 1과 동일하게 "가져오기" 기능 사용

---

## 📝 단계별 체크리스트

### 로컬에서 준비
- [ ] 데이터베이스 백업 완료
- [ ] SQL 덤프 파일 생성 완료
- [ ] 모든 테이블이 포함되었는지 확인

### 호스팅에서 작업
- [ ] 데이터베이스 생성 완료
- [ ] phpMyAdmin 접속 확인
- [ ] SQL 파일 업로드/임포트 완료
- [ ] 모든 테이블이 정상적으로 생성되었는지 확인
- [ ] 샘플 데이터가 정상적으로 임포트되었는지 확인

### 설정 업데이트
- [ ] `config/hosting.php` 파일에 DB 정보 입력
- [ ] 웹사이트에서 DB 연결 테스트
- [ ] 로그인 테스트
- [ ] 데이터 조회 테스트

---

## ⚠️ 주의사항

1. **데이터 백업**: 마이그레이션 전에 반드시 로컬 DB 백업
2. **문자 인코딩**: UTF-8로 설정되어 있는지 확인
3. **파일 크기 제한**: 큰 SQL 파일의 경우 phpMyAdmin 업로드 제한이 있을 수 있음
   - 해결: `php.ini`에서 `upload_max_filesize` 증가 또는 파일 분할
4. **외래 키 제약**: 임포트 시 외래 키 오류가 발생할 수 있음
   - 해결: 임포트 전에 `SET FOREIGN_KEY_CHECKS=0;` 추가

---

## 🔧 문제 해결

### 임포트 오류 발생 시

1. **문자 인코딩 오류**
   - SQL 파일을 UTF-8로 저장했는지 확인
   - phpMyAdmin에서 문자 집합을 `utf8mb4`로 설정

2. **파일 크기 제한**
   - 방법 1: `php.ini` 수정
     ```ini
     upload_max_filesize = 50M
     post_max_size = 50M
     ```
   - 방법 2: 파일 분할 (큰 테이블별로 분리)

3. **외래 키 오류**
   - SQL 파일 맨 위에 추가:
     ```sql
     SET FOREIGN_KEY_CHECKS=0;
     ```
   - SQL 파일 맨 아래에 추가:
     ```sql
     SET FOREIGN_KEY_CHECKS=1;
     ```

4. **타임아웃 오류**
   - phpMyAdmin 설정에서 실행 시간 제한 증가
   - 또는 명령줄로 임포트

---

## 📊 마이그레이션 후 확인 사항

1. **테이블 확인**
   ```sql
   SHOW TABLES;
   ```
   - 모든 테이블이 있는지 확인

2. **데이터 확인**
   ```sql
   SELECT COUNT(*) FROM users;
   SELECT COUNT(*) FROM managers;
   SELECT COUNT(*) FROM service_requests;
   ```
   - 각 테이블의 레코드 수 확인

3. **웹사이트 테스트**
   - 로그인 테스트
   - 데이터 조회 테스트
   - API 동작 테스트

---

## 🚀 빠른 마이그레이션 스크립트

로컬에서 실행할 수 있는 PHP 스크립트를 생성할 수도 있습니다:

`database/export.php` - 데이터베이스 전체 덤프 생성
`database/import.php` - 호스팅 서버에서 실행하여 데이터 임포트

필요하시면 이 스크립트들도 생성해드릴 수 있습니다.
