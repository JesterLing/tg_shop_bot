import React from "react";

import { formatTimesmap } from "../../components/Utils";

const InfoList = ({ bot, nick, type, status, created, lastLogIn }) => {
    let statusText;
    switch(status) {
      case "noToken": 
        statusText = <span className="label accent">Отсутствует BOT токен</span>;
        break;
      case "noUsername": 
        statusText = <span className="label accent">Не указано botUsername</span>;
        break;
      case "APIError": 
        statusText = <span className="label danger">API Telegram недоступно</span>;
        break;
      case "noWebhook": 
        statusText = <span className="label warning">WebHook не задан</span>;
        break;
      case "service": 
        statusText = <span className="label warning">Сервисный режим</span>;
        break;
      case "DBError": 
        statusText = <span className="label danger">Ошибка чтения БД</span>;
        break;
      case "invalidToken": 
        statusText = <span className="label danger">Неверный BOT токен</span>;
        break;
      case "ok": 
        statusText = <span className="label okay">OK</span>;
        break;
      default: 
        statusText = <span className="label warning">{status}</span>;
    }
    return(
      <div className="row">
        <div className="col-2">
          <ul>
            <li><b>Бот:</b> <a href={`https://t.me/${nick}`} target="_blank">{bot}</a></li>
            <li><b>Ник:</b> {nick}</li>
            <li><b>Тип:</b> {type}</li>
          </ul>
        </div>
        <div className="col-2">
          <ul>
            <li><b>Статус:</b> {statusText}</li>
            <li><b>Создан:</b> { created && formatTimesmap(created) }</li>
            <li><b>Последний ваш вход:</b> { lastLogIn && formatTimesmap(lastLogIn) }</li>
          </ul>
        </div>
      </div>
    );
  }

export default InfoList;
