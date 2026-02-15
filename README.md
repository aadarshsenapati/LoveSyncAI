# ❤️ LoveSync AI

**Measure, cherish, and strengthen human connections through AI.**

LoveSync AI is a comprehensive **Relationship Intelligence & Connection Platform** designed to enhance emotional bonding for both couples and singles. It combines relationship analytics, memory preservation, celebration reminders, AI emotional tools, and community spaces — all under the hackathon theme **"Code for Connection."**

---

## ✨ Key Features

### 🌍 For Everyone

* **User Profiles** – Manage personal details, birth data, and relationship status.
* **AI‑Powered Wish Maker** – Generate heartfelt messages (birthday, love, anniversary, apology) using Groq AI, then send or schedule emails.

---

### 💙 For Singles

* **Blind Dating** – Match with others based on astrological compatibility and interests. Chat anonymously until both reveal identities.
* **Singles Connect Zone** – Moderated chatrooms with AI conversation starters and compatibility lite.

---

### ❤️ For Couples

* **Relationship Pairing** – Generate a unique code to link with your partner.
* **Astrology Compatibility** – Detailed Guna Milan reports and planetary positions based on birth details.
* **WhatsApp Chat Analyzer** – Upload exported `.zip` chats to receive love scores, message statistics, longest streaks, and “I love you” counters.
* **Memory Vault** – Securely store photos and videos in a shared vault (powered by a separate API) with analytics.
* **Distance Tracker** – Real‑time GPS tracking showing how far apart partners are, with live map view.
* **Anniversary Countdown** – Track days together and days until the next anniversary.
* **Email Scheduler** – Schedule romantic emails for birthdays, anniversaries, or surprise notes.

---

## 🛠️ Technology Stack

| Layer               | Technologies                                           |
| ------------------- | ------------------------------------------------------ |
| **Frontend**        | HTML5, CSS3, JavaScript, Chart.js, Leaflet             |
| **Backend**         | PHP (Native), MySQL                                    |
| **APIs & Services** | Groq AI, Custom Flask APIs (Astro‑Match, Vault, Email) |
| **Libraries**       | cURL, Font Awesome, Google Fonts                       |
| **Database**        | MySQL (`assets/connection.php`)                        |

---

## 🚀 Installation & Setup

### 📋 Prerequisites

* PHP 7.4+ with MySQLi & cURL extensions
* MySQL Server
* Composer *(optional)*
* Apache / Nginx Web Server

---

### ⚙️ Steps

#### 1️⃣ Clone the Repository

```bash
git clone https://github.com/aadarshsenapati/LoveSyncA
cd lovesync-ai
```

---

#### 2️⃣ Configure Database

* Create a MySQL database (e.g., `lovesync_db`).
* Import the provided SQL schema *(if available)*.
* Update database credentials in:

  ```
  assets/connection.php
  ```

---

#### 3️⃣ Set Up API Endpoints

Ensure the following services are running:

| Service         | Endpoint                            |
| --------------- | ----------------------------------- |
| Astro‑Match API | `http://127.0.0.1:5004/astro-match` |
| Vault API       | `http://127.0.0.1:5002/api/...`     |
| Email API       | `http://127.0.0.1:5003/...`         |

Update URLs in PHP files if deployed remotely.

---

#### 4️⃣ Configure Groq API Key

In `wish.php`, replace:

```php
$apiKey = "YOUR_GROQ_KEY";
```

---

#### 5️⃣ Set Folder Permissions

Ensure writable permissions for:

```
uploads/reveals/
```

Used for blind‑date reveal image uploads.

---

#### 6️⃣ Run the Application

Place the project inside your web server root:

```
http://localhost/lovesync-ai
```

---

## 📁 Project Structure

```
lovesync-ai/
├── assets/
│   └── connection.php          # Database connection
├── uploads/
│   └── reveals/                # Reveal images
├── index.html                  # Landing page
├── login.php                   # User login
├── signup.php                  # User registration + astro data
├── dashboard.php               # Role-based dashboard
├── profile.php                 # Profile management
├── wish.php                    # AI wish maker & scheduler
├── astro.php                   # Compatibility reports
├── whatsapp.php                # Chat analytics dashboard
├── upload_chat.php             # Chat .zip upload
├── vault.php                   # Couple memory vault
├── chat.php                    # Blind dating chat
├── reveal.php                  # Identity reveal system
├── logout.php                  # Logout
└── README.md
```

---

## 🗄️ Database Schema (Key Tables)

### 👤 users

| Field               | Type         | Description           |
| ------------------- | ------------ | --------------------- |
| id                  | int          | Primary key           |
| email               | varchar(255) | User email            |
| password            | varchar(255) | *(Upgrade to hashed)* |
| full_name           | varchar(255) | Name                  |
| gender              | enum         | Gender                |
| dob                 | date         | Date of birth         |
| tob                 | time         | Time of birth         |
| pob                 | varchar(255) | Place of birth        |
| latitude            | decimal      | Location              |
| longitude           | decimal      | Location              |
| relationship_status | enum         | Single / Committed    |

---

### 💑 relationships

| Field              | Type        | Description  |
| ------------------ | ----------- | ------------ |
| id                 | int         | Primary key  |
| partner1_id        | int         | User ID      |
| partner2_id        | int         | User ID      |
| pair_code          | varchar(20) | Pairing code |
| anniversary        | date        | Anniversary  |
| relationship_start | date        | Start date   |
| vault_id           | varchar(50) | Vault API ID |

---

### 🕵️ blind_matches

| Field              | Type | Description                   |
| ------------------ | ---- | ----------------------------- |
| id                 | int  | Primary key                   |
| user1_id           | int  | User                          |
| user2_id           | int  | User                          |
| reveal_status      | enum | Hidden / Revealed / Ended     |
| user1_revealed     | bool | Reveal flag                   |
| user2_revealed     | bool | Reveal flag                   |
| user1_reveal_image | text | Image path                    |
| user2_reveal_image | text | Image path                    |
| user1_decision     | enum | Pending / Accepted / Rejected |
| user2_decision     | enum | Pending / Accepted / Rejected |

---

### 📊 Analytics Tables

* `chat_reports`
* `chat_user_metrics`
* `love_moments`

Stores WhatsApp analysis insights.

---

### 🔮 astro_couple_reports

| Field                 | Type        | Description     |
| --------------------- | ----------- | --------------- |
| id                    | int         | Primary key     |
| relationship_id       | int         | Relationship    |
| guna_milan_score      | varchar(20) | e.g. 25/36      |
| compatibility_verdict | text        | Verdict         |
| summary               | text        | Detailed report |

---

## 🧪 Usage Examples

### 💑 Pair With a Partner

1. Go to Dashboard.
2. Generate a Pair Code.
3. Share with your partner.
4. Partner enters code + anniversary.

---

### 📱 Analyze WhatsApp Chat

1. Export chat as `.zip`.
2. Upload via **Upload Chat**.
3. View love score, streaks, first “I love you”, etc.

---

### 💌 Schedule AI Email

1. Open Wish Maker.
2. Enter recipient + message type.
3. Generate message.
4. Edit if needed.
5. Schedule date/time.

---

## 🤝 Contributing

1. Fork the repository.
2. Create a feature branch:

   ```bash
   git checkout -b feature/AmazingFeature
   ```
3. Commit changes:

   ```bash
   git commit -m "Add Amazing Feature"
   ```
4. Push:

   ```bash
   git push origin feature/AmazingFeature
   ```
5. Open Pull Request.

---

## 📄 License

Licensed under the **MIT License**.
See `LICENSE` file for details.

---

## 🙏 Acknowledgements

* Groq – LLM API
* Font Awesome – Icons
* Leaflet – Maps
* Chart.js – Visualizations
* Code for Connection Hackathon Community

---

> Built with ❤️ to bring people closer through technology.
