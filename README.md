# Classical Ciphers — Group 3

A web-based encryption and decryption tool for five classical ciphers, built with PHP, HTML, CSS, and JavaScript.

---

## 📁 File Structure

```
Classical Ciphers/
├── index.php    ← PHP cipher logic + HTML structure
├── style.css    ← All styles and layout
├── app.js       ← All JavaScript (UI, AJAX, live previews)
└── README.md    ← This file
```

---

## 🚀 How to Run

### Requirements
- **XAMPP** or **WAMP** (any local PHP server)
- PHP 7.4 or higher
- A browser (Chrome, Firefox, Edge)

### Steps
1. Copy the project folder into your server's root directory:
   - XAMPP → `C:/xampp/htdocs/Classical Ciphers/`
   - WAMP  → `C:/wamp64/www/Classical Ciphers/`
2. Start **Apache** from XAMPP/WAMP Control Panel
3. Open your browser and go to:
   ```
   http://localhost/Classical Ciphers/index.php
   ```

---

## 🔐 Ciphers

### 1. Playfair
- Encrypts **pairs of letters** (digraphs) using a **5×5 key square**
- Letters **I and J share** one cell
- Duplicate letter pairs are separated by **X** (or **Q** if the pair is XX)
- **Key:** Any word or phrase (keyword builds the square)

| Input        | Key     | Output   |
|--------------|---------|----------|
| HELLO WORLD  | KEYWORD | GYIZSC…  |

---

### 2. Pigpen (Masonic)
- Replaces each letter with a **geometric symbol** from a tic-tac-toe or X-shaped grid
- Output is stored as **JSON token array** for easy copy-paste decoding
- No key required

| Input | Output (tokens)         |
|-------|-------------------------|
| HELLO | `[~~]` `[ | ]` `< .>` … |

To decode: copy the encoded JSON output and paste it back into the decode input.

---

### 3. Hill (2×2)
- Uses **matrix multiplication** mod 26 on pairs of letters
- Key is **4 letters** that form a 2×2 matrix: `[k0 k1] / [k2 k3]`
- The matrix **must be invertible** mod 26 (det must have an inverse mod 26)
- Plaintext is padded with **X** if its length is odd

| Input | Key  | Output |
|-------|------|--------|
| HELP  | GYBN | HIAT   |

> ⚠ If you get "Key matrix not invertible", try a different 4-letter key.

---

### 4. Affine
- Formula: **E(x) = (a·x + b) mod 26**
- Decryption: **D(x) = a⁻¹·(x − b) mod 26**
- **Key a** must be coprime with 26

Valid values for `a`: `1, 3, 5, 7, 9, 11, 15, 17, 19, 21, 23, 25`

| Input       | a | b | Output      |
|-------------|---|---|-------------|
| HELLO WORLD | 5 | 8 | RCLLA OAPLX |

---

### 5. Vigenère
- Extends Caesar cipher using a **repeating keyword**
- Each keyword letter defines the shift for the corresponding plaintext letter
- Non-letter characters (spaces, punctuation) are preserved

| Input          | Key   | Output         |
|----------------|-------|----------------|
| ATTACK AT DAWN | LEMON | LXFOPV EF RNHR |

The UI shows a **PT / Key / CT alignment table** after each operation.

---

## 🧩 How the Code Is Organized

### `index.php`
- Contains **all PHP cipher functions** (Playfair, Pigpen, Hill, Affine, Vigenère)
- Handles **AJAX POST requests** (returns JSON to `app.js`)
- Renders the **HTML page** with cipher panels

### `style.css`
- CSS variables for the full color theme (`--bg`, `--accent`, `--gold`, etc.)
- Styles for tabs, cards, inputs, buttons, result boxes
- Playfair key square and Pigpen chart layouts
- Animations (`fadeUp`) and scrollbar customization

### `app.js`
- `switchTab(n)` — switches between cipher panels
- `setMode(btn, mode)` — toggles Encrypt / Decrypt per panel
- `run(cipher)` — collects inputs, POSTs to `index.php`, calls `showResult()`
- `showResult(cipher, data)` — renders result or error
- `copyResult(id)` — copies result text to clipboard
- `updatePFSquare()` — renders live 5×5 Playfair key square
- `updateHillMatrix()` — renders live 2×2 Hill key matrix with determinant
- `buildPigpenChart()` — builds the Pigpen alphabet reference chart

---

## ⚠ Common Errors

| Error | Cause | Fix |
|-------|-------|-----|
| Key matrix not invertible mod 26 | Hill key det has no inverse | Try a different 4-letter key (e.g. GYBN, HILL) |
| 'a' must be coprime with 26 | Affine key `a` is invalid | Use: 1,3,5,7,9,11,15,17,19,21,23,25 |
| Key cannot be empty | Vigenère key is blank | Enter a keyword |
| STDIN error | Running through web server | Use `http://localhost/…`, not CLI |

---

## 👥 Group 3 Members

> *(Add your names here)*

---

## 📌 Notes

- All ciphers operate on **uppercase letters A–Z** only
- Spaces and punctuation are **preserved** (except in Playfair and Hill which strip them)
- Vigenère accepts numeric keys (digits 1–9 map to letters A–I)
