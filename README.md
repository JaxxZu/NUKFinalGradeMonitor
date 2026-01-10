# NUKFinalScoreMonitor
國立高雄大學期末成績監控通知系統

## 自行部署教程
### 一、配置TG BOT
1. 向 @BotFather 發送 /newbot 創建bot，獲取api密鑰  
<img width="50%" height="988" alt="image" src="https://github.com/user-attachments/assets/8903c61e-ab25-4c5b-9a47-47b4c9f166aa" />
  
2. 向bot發送/start  
<img width="50%" height="839" alt="image" src="https://github.com/user-attachments/assets/099edcaa-00c5-4e3a-8f8d-89071debc4c8" />

### 二、配置驗證碼自動打碼
1. 安裝 `sudo apt install -y screen python3-venv python3-pip`  
2. 啟用虛擬桌面 `screen -S ddddocr`
3. 下載 ddddocr-fastapi資料夾
4. cd 到 ddddocr-fastapi資料夾 `cd ./ddddocr-fastapi`  
5. 配置虛擬環境 `python3 -m venv ddddocr`  
6. 激活虛擬環境 `source ddddocr/bin/activate `  
7. `pip install -r requirements.txt`  
8. 運行ddddocr自動打碼`python3 -m app.main`
