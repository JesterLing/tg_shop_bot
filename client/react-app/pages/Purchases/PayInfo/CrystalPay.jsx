import React from "react";

const CrystalPay = ({ info }) => {
  return (
      <ul>
        <li>ID: {info?.id}</li>
        <li>Ошибка: {info?.error ? info?.error : 'Нет'}</li>
        <li>Метод: {(info?.paymethod !== null) ? info?.paymethod : 'Нет'}</li>
      </ul>
  );
}

export default CrystalPay;
