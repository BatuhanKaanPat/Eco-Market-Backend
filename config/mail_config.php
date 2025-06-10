<?php
/**
 * Mail configuration settings
 * Bilkent University SMTP Server Configuration
 */

// Bilkent Email Credentials - Update with your credentials
const EMAIL = 'altug.ucar@ug.bilkent.edu.tr';   // Your Bilkent email
const EMAIL_PASSWORD = '4uecsNoG';                 // Your email password
const FULLNAME = 'Eco Market';                        // Your name or service name

// Bilkent SMTP Server details
const SMTP_HOST = 'asmtp.bilkent.edu.tr';            // Bilkent SMTP server
const SMTP_PORT = 587;                               // SMTP port
const SMTP_SECURITY = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;  // Encryption type

// Debug level (0 = no debug output, 1 = client messages, 2 = client and server messages)
const DEBUG_LEVEL = 0; 