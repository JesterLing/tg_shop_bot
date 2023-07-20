import React from 'react';
import { useSelector } from 'react-redux';

import Button from '../../components/UI/Button';
import { formatBytes } from '../../components/Utils';
import CrystalPay from './PayInfo/CrystalPay';
import Qiwi from './PayInfo/Qiwi';
import Coinbase from './PayInfo/Coinbase';
import { useGetPurchQuery, useGetPaymentQuery } from '../../service/API';

import styles from './DetailsSubRow.module.less';

const DetailsSubRow = ({ row, rowProps, visibleColumns, setContentModal }) => {
  const { type, currency } = useSelector((state) => state.global.configs);
  const {
    data: purch,
    isLoading: isPurchLoading,
    isError: isPurchError,
    error: purchError
  } = useGetPurchQuery(row.original.id);
  const {
    data: pay,
    isLoading: isPayLoading,
    isUninitialized: isUinit,
    isError: isPayError,
    error: payError
  } = useGetPaymentQuery(row.original.payment.id, { skip: !row.original.payment.id });

  if (isPurchLoading || isPayLoading) {
    return (
      <tr>
        <td />
        <td colSpan={visibleColumns.length - 1}>Загрузка...</td>
      </tr>
    );
  }
  if (isPurchError) {
    return (
      <tr>
        <td />
        <td colSpan={visibleColumns.length - 1}>{purchError.data?.message}</td>
      </tr>
    );
  }
  if (isPayError) {
    return (
      <tr>
        <td />
        <td colSpan={visibleColumns.length - 1}>{payError.data?.message}</td>
      </tr>
    );
  }
  return (
    <tr>
      <td />
      <td colSpan={visibleColumns.length - 1}>
        {purch.length ? (
          <table className={styles.internal}>
            <thead>
              <tr>
                <th scope="col">Категория</th>
                <th scope="col">Товар</th>
                <th scope="col">Кол-во</th>
                <th scope="col">Скидка</th>
                <th scope="col">Цена</th>
                {!!type && <th scope="col">Содержимое</th>}
              </tr>
            </thead>
            <tbody>
              {purch.map((row, i) => {
                let con = row.content;
                if (row.content_type === 'FILE') {
                  con = (
                    <ul>
                      {row.content.map((file, ix) => (
                        <li key={ix}>
                          <div>
                            <a href={file.path} target="_blank">
                              {file.name}
                            </a>
                          </div>
                          <div>{formatBytes(file.size)}</div>
                        </li>
                      ))}
                    </ul>
                  );
                }
                return (
                  <tr key={i}>
                    <td>{row.category}</td>
                    <td>{row.product}</td>
                    <td>{row.quantity}</td>
                    <td>
                      {row.discount}
                      {row.dis_percent}
                    </td>
                    <td>
                      {row.price}
                      {currency}.
                    </td>
                    {!!type && (
                      <td>
                        <Button
                          icon="eye"
                          color="blue"
                          hollow={true}
                          compact={true}
                          onClick={() => setContentModal({ visible: true, content: con })}
                        />
                      </td>
                    )}
                  </tr>
                );
              })}
            </tbody>
          </table>
        ) : (
          'Нет данных для отображения'
        )}

        {!isUinit && pay.service != 0 && (
          <div className="row mt-20">
            <div className="col-2">
              {(pay.service === 1 && <CrystalPay info={pay.additionally} />) ||
                (pay.service === 2 && <Qiwi info={pay.additionally} />) ||
                (pay.service === 3 && <Coinbase info={pay.additionally} />)}
            </div>
            <div className="col-2">
              <ul>
                <li>Последняя проверка: {pay.last_check}</li>
              </ul>
            </div>
          </div>
        )}
      </td>
    </tr>
  );
};

export default DetailsSubRow;
