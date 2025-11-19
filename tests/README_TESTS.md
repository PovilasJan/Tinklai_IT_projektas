# Testavimo dokumentacija

## Aplinka
- **Serveris**: Laragon (WAMP ekvivalentas)
- **PHP versija**: 8.3.16 (CLI)
- **Duomenų bazė**: MySQL
- **Operacinė sistema**: Windows 11

## Prisijungimo duomenys

| Slapyvardis | Slaptažodis | El. paštas | Rolė |
|------------|-------------|------------|------|
| adminas | admin123 | admin@hotel.lt | Administratorius |
| darbuotojas | darbuotojas123 | employee@hotel.lt | Darbuotojas |
| klientas | klientas123 | client@hotel.lt | Vartotojas |

## Kaip paleisti testus

### Pasiruošimas
1. Įsitikinkite, kad duomenų bazė sukurta ir užpildyta:
   ```bash
   # Atidaryti naršyklėje
   http://localhost:8000/setup.php
   ```

2. Eiti į projekto katalogą:
   ```bash
   cd c:\Users\janka\OneDrive\Desktop\tinklai\viesbuciu_tinklas
   ```

### Paleidžiami testai

**Pastaba:** Naudokite pilną kelią iki PHP arba PowerShell sintaksę.

#### 1. Registracijos testas
```powershell
& "C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe" tests/test_register.php
```
Testuoja:
- Teisingą registraciją
- Neteisingą el. pašto formatą
- Tuščią vardą
- Per trumpą slaptažodį
- Dubliuotą el. paštą
- Tuščią slaptažodį

#### 2. Prisijungimo testas
```powershell
& "C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe" tests/test_login.php
```
Testuoja:
- Teisingą admin prisijungimą
- Neteisingą slaptažodį
- Neegzistuojantį vartotoją
- Tuščią el. paštą
- Teisingą kliento prisijungimą
- Neteisingą el. pašto formatą

#### 3. Viešbučio operacijų testas
```powershell
& "C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe" tests/test_hotel_operations.php
```
Testuoja:
- Viešbučio pridėjimą
- Tuščią pavadinimą
- Neteisingą įvertinimą
- Viešbučio ištrynimą
- Neegzistuojančio viešbučio ištrynimą

#### 4. Rezervacijų operacijų testas
```powershell
& "C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe" tests/test_reservation_operations.php
```
Testuoja:
- Rezervacijos sukūrimą
- Rezervacijos patvirtinimą
- Rezervacijos atšaukimą
- Neteisingą datų intervalą
- Persidengiančias rezervacijas

#### 5. Nuolaidų kodų testas
```powershell
& "C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe" tests/test_discount_codes.php
```
Testuoja:
- Nuolaidos kodo sukūrimą
- Neteisingą nuolaidos procentą
- Dubliuotą kodą
- Tuščią kodą
- Kodo aktyvavimą/deaktyvavimą

#### 6. Naujienlaiškio testas
```powershell
& "C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe" tests/test_newsletter.php
```
Testuoja:
- Prenumeratos užsakymą
- Dubliuotą prenumeratą
- Prenumeratos atšaukimą
- Neteisingą el. paštą
- Svečio prenumeratą

#### 7. Kambarių paieškos testas
```powershell
& "C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe" tests/test_room_search.php
```
Testuoja:
- Paiešką pagal miestą
- Paiešką pagal vietų skaičių
- Paiešką pagal maksimalią kainą
- Paiešką pagal viešbučio įvertinimą
- Paiešką su datų prieinamumu
- Kombinuotą paiešką

#### 8. Naudotojų peržiūros testas
```powershell
& "C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe" tests/test_users_view.php
```
Testuoja:
- Visų naudotojų peržiūrą
- Peržiūrą pagal rolę (admin)
- Peržiūrą pagal rolę (employee)
- Peržiūrą pagal rolę (client)
- Paieška pagal el. paštą
- Naujienlaiškio prenumeratorių peržiūrą

### Visi testai vienu metu
```powershell
& "C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe" tests/test_register.php ; & "C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe" tests/test_login.php ; & "C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe" tests/test_hotel_operations.php ; & "C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe" tests/test_reservation_operations.php ; & "C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe" tests/test_discount_codes.php ; & "C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe" tests/test_newsletter.php ; & "C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe" tests/test_room_search.php ; & "C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe" tests/test_users_view.php
```

## Testuojamos funkcijos

1. ✅ **Registruotis** - test_register.php
2. ✅ **Prisijungti** - test_login.php
3. ✅ **Atsijungti** - (sesijos funkcija, testuojama per UI)
4. ✅ **Ieškoti kambarių** - test_room_search.php
5. ✅ **Žiūrėti viešbučio kambarius** - test_room_search.php
6. ✅ **Rezervuoti kambarį** - test_reservation_operations.php
7. ✅ **Užsiregistruoti naujienlaiškiui** - test_newsletter.php
8. ✅ **Pridėti viešbutį** - test_hotel_operations.php
9. ✅ **Ištrinti viešbutį** - test_hotel_operations.php
10. ✅ **Patvirtinti rezervaciją** - test_reservation_operations.php
11. ✅ **Atšaukti rezervaciją** - test_reservation_operations.php
12. ✅ **Sukurti nuolaidos kodą** - test_discount_codes.php
13. ✅ **Peržiūrėti naudotojus** - test_users_view.php
14. ✅ **Peržiūrėti užsiregistravusius naujienlaiškiui** - test_users_view.php
15. ✅ **Užregistruoti naują paskyrą** - test_register.php

## Pastabos

- Visi testai automatiškai išvalo sukurtus duomenis (cleanup)
- Testai nenaudoja sesijų - tiesiogiai dirba su duomenų baze
- Kiekvienas testas yra nepriklausomas ir gali būti paleistas atskirai
- Rezultatai rodomi terminale realiu laiku
