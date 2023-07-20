import React from "react";

const Qiwi = ({ info }) => {
    return (
        <ul>
          <li>Bill ID: {info?.billId}</li>
          <li>Status: {info?.status?.value}</li>
          <li>Метод: {info?.paymentMethod}</li>
        </ul>
    );
}

export default Qiwi;
