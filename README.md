<div align="center">
<img width="1200" height="475" alt="GHBanner" src="https://github.com/user-attachments/assets/0aa67016-6eaf-458a-adb2-6e31a0763ed6" />
</div>

# Run and deploy your AI Studio app

This contains everything you need to run your app locally.

View your app in AI Studio: https://ai.studio/apps/drive/1YmxhoA55M2la42FGS_J3oklxUVYyjvrD

## Run Locally

**Prerequisites:**
- PHP (with SQLite and PDO extensions enabled)

1. **Start the Server:**
   Open a terminal in the project root and run:
   `php -S localhost:8000`

2. **Access the App:**
   - **Localhost:** Open http://localhost:8000
   - **Local Network:** Open `http://<YOUR_IP_ADDRESS>:8000` on other devices (e.g., phone).
     - Find your IP by running `ipconfig` in the terminal.

**Notes:**
- The database is a single file `database.sqlite` created automatically in the project root.
- Uploaded images are stored in the `uploads/` directory.
- This is a pure PHP application. No Node.js or npm required.
