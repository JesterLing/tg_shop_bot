import React from "react";

const Coinbase = ({ info }) => {
    return (
        <ul>
          <li>ID: {info?.data?.id}</li>
          <li>Цена: {info?.data?.pricing?.local.amount}{info?.data?.pricing?.local.currency} / {info?.data?.pricing?.bitcoin.amount}{info?.data?.pricing?.bitcoin.currency}</li>
          <li>Транзакция: {info?.data?.payments[0]?.transaction_id}</li>
        </ul>
    );
}

export default Coinbase;
