# QR Code Security Implementation - Guide Complet

## Vue d'ensemble

Cette implémentation sécurisée de vérification QR code suit les meilleures pratiques OWASP et les recommandations du gouvernement français (ANSSI) pour la sécurité cryptographique.

---

## Architecture de Sécurité

### 1. **Génération Sécurisée du Token**

```
Token brut (unique_code) = 40 caractères hex de haute entropie
  ├─ 48 caractères hex from random_bytes(24) [192 bits]
  ├─ + timestamp hex
  └─ Tronqué à 40 caractères

Stockage en BD: SHA256(unique_code) = token_hash
```

**Avantages:**
- Si la BD fuit, les tokens bruts ne peuvent pas être recréés
- Token suffisamment long (40 chars) pour résister aux attaques par force brute
- Utilise `random_bytes()` pour une entropie cryptographique (CSPRNG)

### 2. **Flow de Vérification**

```
Scanner QR
   │
   ▼
Extrait URL: /api/qr/verify/{unique_code}
   │
   ├─ [1] Rate Limiting check (max 50 req/15min par IP)
   │
   ├─ [2] Format validation du token
   │
   ├─ [3] Calcul token_hash = SHA256(token_brut)
   │
   ├─ [4] Requête DB: SELECT * WHERE token_hash = ?
   │
   ├─ [5] Vérifications de validité
   │       ├─ Expiration (expires_at <= now())
   │       └─ Statut === 'active'
   │
   ├─ [6] Déchiffrement payload (AES-256)
   │
   ├─ [7] Validation HMAC en temps constant
   │       └─ hash_equals() pour éviter timing attacks
   │
   ├─ [8] Retour données anonymisées
   │
   └─ [9] Logging & Audit trail
```

### 3. **Sécurité des Signatures HMAC**

```
Génération (lors de la création du QR):
HMAC = hash_hmac('sha256', 
  unique_code + '|' + payload_encrypted, 
  env('QR_ENCRYPTION_KEY')
)

Validation (lors de la vérification):
expected_hash = hash_hmac(...)
hash_equals(expected_hash, qrStamp->signature_hash)
  ├─ Comparaison en TEMPS CONSTANT
  └─ Immune aux timing attacks
```

**Important:** `hash_equals()` != `==` (ce dernier sort en cas de mismatch rapide)

### 4. **Chiffrement des Données**

```
Payload (JSON):
{
  "signatory_id": "...",
  "full_name": "...",
  "position": "...",
  "company": {...},
  "issued_at": "..."
}
   │
   ▼
Crypt::encryptString(json_encode($payload))
   │
   ├─ Algorithme: AES-256-CBC (Laravel default)
   ├─ Clé: env('APP_KEY') [base64]
   ├─ IV: Random (inclus dans le ciphertext)
   └─ Authentification: automatique (Encrypt-then-MAC)
   │
   ▼
payload_encrypted (stockée en BD)
```

---

## Mesures de Rate Limiting & Brute-Force

### Configuration Recommandée

```php
// routes/api.php
Route::get('/verify/{token}', [...])
  ->middleware('throttle:50,15'); // 50 req/15min par IP
```

### Déection de Tentatives Suspectes

Chaque tentative échouée est loggée:

```
Raisons de log:
- INVALID_TOKEN : format invalide
- QR_NOT_FOUND : token_hash introuvable
- QR_EXPIRED : expires_at < now
- QR_INACTIVE : status !== 'active'
- INVALID_SIGNATURE : tampering détecté
```

**Alerte automatique:**
- Si 5+ tentatives suspectes depuis la même IP en 1h
- Log niveau `alert` + notification (à intégrer: Slack/Email)

---

## Audit Trail & Logging

### Champs d'Audit sur QrStamp

```
verification_count : nombre de scans réussis
verification_attempts : tentatives totales (reset périodiquement)
last_suspicious_attempt : timestamp de la dernière tentative échouée
last_suspicious_ip : IP de la dernière tentative échouée
last_suspicious_user_agent : UA de la dernière tentative échouée
```

### Logs Structurés

```
[INFO] QR Verification: Success
  qr_stamp_id: 12345
  ip: 192.168.1.1
  verification_count: 42

[WARNING] QR Verification: Suspicious attempt
  reason: INVALID_SIGNATURE
  ip: 192.168.1.1
  timestamp: 2025-12-07T...

[ALERT] QR Verification: Suspicious threshold reached
  ip: 192.168.1.1
  suspicious_count: 5
```

---

## Protection Contre les Attaques

### 1. **Brute-Force Attacks**
- ✅ Rate limiting (50 req/15min)
- ✅ Token long (40 chars) = 2^160 possibilités
- ✅ Détection d'IP suspectes
- ✅ Logging automatique

### 2. **Timing Attacks**
- ✅ `hash_equals()` pour comparaisons sensibles
- ✅ HMAC pour intégrité du payload
- ✅ Même durée de calcul peu importe où le mismatch se produit

### 3. **Tampering / Modification de Payload**
- ✅ Signature HMAC validation
- ✅ Chiffrement AES-256
- ✅ IV random à chaque création

### 4. **Replay Attacks**
- ✅ Timestamp dans le QR (issued_at)
- ✅ Expiration configurable (par défaut 1 an)
- ✅ Status field (active/revoked/expired)

### 5. **Injection / XSS**
- ✅ Données retournées en JSON (API)
- ✅ Pas de HTML généré côté serveur
- ✅ Client-side doit faire l'échappement

### 6. **Information Disclosure**
- ✅ Données sensibles anonymisées/partielles
- ✅ Pas de révélation d'erreur détaillées aux attaquants
- ✅ Signature_image flag seulement (pas la data brute)
- ✅ Email/Phone partiels possibles

---

## Mise en Place

### 1. **Migration Base de Données**

```bash
php artisan migrate
# Exécute: 2025_12_07_000001_add_security_fields_to_qr_stamps.php
```

Ajoute:
- `token_hash` (UNIQUE, INDEX)
- `verification_attempts`
- `last_suspicious_attempt`
- `last_suspicious_ip`
- `last_suspicious_user_agent`

### 2. **Mise à Jour de l'Environnement**

```env
# .env
QR_ENCRYPTION_KEY=your_secret_key_here # ou utiliser APP_KEY
RATE_LIMIT_DECAY=15 # minutes
```

### 3. **Routes API**

```php
// routes/api.php
Route::get('/qr/verify/{token}', [QrVerificationController::class, 'verify'])
  ->name('qr.verify')
  ->middleware('throttle:50,15');
```

### 4. **Test d'Intégration**

```bash
# Créer un QR
$qr = $signatory->qrStamp()->create([...]);
// URL générée dans QR: https://stamptechivoire.local/api/qr/verify/ABC123...

# Scanner le QR code
GET https://stamptechivoire.local/api/qr/verify/ABC123...

# Réponse:
{
  "success": true,
  "message": "Code QR valide.",
  "data": {
    "full_name": "Jean Dupont",
    "position": "Directeur",
    "company_name": "ACME Corp",
    "issued_at": "2025-12-01T10:00:00Z",
    "expires_at": "2026-12-01T10:00:00Z",
    "verification_count": 42,
    "has_signature": true
  }
}
```

---

## Recommandations Supplémentaires

### 1. **Rotation de Clé**

```php
// Si la clé de chiffrement change
php artisan qr:rotate-key --from=v1.0 --to=v2.0
```

À implémenter: Command Laravel qui re-chiffre tous les QR avec la nouvelle clé.

### 2. **HTTPS Obligatoire**

```php
// config/app.php
'force_https' => env('APP_ENV') === 'production',

// middleware
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
```

### 3. **CORS Configuration**

```php
// config/cors.php
'paths' => ['api/*'],
'allowed_origins' => ['https://yourdomain.com'],
'allowed_methods' => ['GET'],
'max_age' => 0,
```

### 4. **Alerting & Monitoring**

Intégrer:
- Sentry (error tracking)
- Slack (suspicious attempts)
- Datadog (performance monitoring)

```php
if ($suspiciousCount >= 5) {
    // Notifier via Slack
    Notification::route('slack', env('SLACK_WEBHOOK'))
        ->notify(new SuspiciousQrAttempts($ip, $count));
}
```

### 5. **Compliance & Audit**

- ✅ Logs conservés 1 an minimum
- ✅ GDPR compliant (anonymisation IP possible)
- ✅ Traçabilité complète (qui a créé le QR, qui l'a scanné)
- ✅ Révocation possible à tout moment

---

## Checklist de Déploiement

- [ ] Migration exécutée
- [ ] Routes API ajoutées
- [ ] `QR_ENCRYPTION_KEY` configurée
- [ ] Rate limiting testé
- [ ] HTTPS activé
- [ ] Logs configurés et sauvegardés
- [ ] CORS configuré
- [ ] Tests d'intégratio passent
- [ ] Monitoring/Alerting en place
- [ ] Documentation utilisateur mise à jour
- [ ] Formation équipe support

---

## Ressources & Références

- [OWASP Top 10](https://owasp.org/Top10/)
- [Laravel Security Best Practices](https://laravel.com/docs/security)
- [ANSSI Recommandations Cryptographie](https://www.anssi.gouv.fr/)
- [CWE-208: Observable Timing Discrepancy](https://cwe.mitre.org/data/definitions/208.html)
- [RFC 4648: Base Encoding](https://tools.ietf.org/html/rfc4648)

