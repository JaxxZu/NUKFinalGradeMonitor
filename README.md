# NUKFinalScoreMonitor
國立高雄大學期末成績監控通知系統

## 核心功能
定期自動登入國立高雄大學平台取得期末成績，若偵測到任何一科成績出分，立即透過 Telegram 發送通知

## 自行部署教程
以Linux伺服器為例  
伺服器運行環境：Ubuntu 24.04、Python 3.12、PHP 7.4  
### 一、配置TG BOT
1. 向 [@BotFather](https://t.me/BotFather) 發送 /newbot 創建bot，獲取api密鑰  
  <img width="50%" alt="image" src="https://github.com/user-attachments/assets/8903c61e-ab25-4c5b-9a47-47b4c9f166aa" />
  
2. 向bot發送/start  
  <img  width="50%"  alt="image" src="https://github.com/user-attachments/assets/099edcaa-00c5-4e3a-8f8d-89071debc4c8" />

3. 使用 @GetAnyUserIDBot 或第三方tg客戶端獲取自己TG帳號id

### 二、配置驗證碼自動打碼
1. 安裝 `sudo apt install -y screen python3-venv python3-pip`  
2. 啟用screen `screen -S ddddocr`
3. `git clone https://github.com/JaxxZu/NUKFinalScoreMonitor.git`
4. cd 到 ddddocr-fastapi資料夾 `cd ./NUKFinalGradeMonitor/ddddocr-fastapi`  
5. 配置虛擬環境 `python3 -m venv ddddocr`  
6. 激活虛擬環境 `source ddddocr/bin/activate`   
7. `pip install -r requirements.txt`  
8. 運行ddddocr自動打碼`python3 -m app.main`

運行後，打碼服務使用8000埠對本機開放

### 三、配置監控主程式
1. 填寫腳本：
```php
$stu_id       = ''; //學號
$password     = ''; //選課平台密碼
$tg_bot_token = ''; //tg bot api密鑰
$tg_chat_id   = ''; //TG id(人/頻道/群)
```   
2. 使用cron定時執行php腳本（示範為每59分鐘檢查一次）  
   `*/59 * * * * /path/to/php /path/to/NUKFinalGradeMonitor/monitor.php`  
   或使用圖形化界面 (示範使用aapanel)  
  <img  width="70%"  src="https://github.com/user-attachments/assets/65c86d03-b2bd-45f1-ad6c-f08a35aa88f8" />

  *注：時間間隔過短有機率觸發學校防火牆

（可選）查看執行日誌  
  <img  width="70%"  src="https://github.com/user-attachments/assets/076fa042-fa55-4222-bfcd-845573d43f0a" />


## 運行結果
腳本首次運行及每次成績變化能取得提醒    
<img width="50%" src="https://github.com/user-attachments/assets/a3c8af02-1d20-4629-b119-22967fa9eccf" />
  

## 流程圖
<img width="48%" alt="Mermaid Chart - Create complex, visual diagrams with text -2026-01-10-134051" src="./flowchart.png" />


## 注意事項
校外網路存取教務系統登入頁會跳驗證碼，故需打碼  
本程式未對校內網路或使用學校VPN情況下，登入頁沒有驗證碼的情況下進行測試  
    
本項目使用 [ddddocr-fastapi](https://github.com/sml2h3/ddddocr-fastapi) 進行自動打碼，基於 MIT 授權。
