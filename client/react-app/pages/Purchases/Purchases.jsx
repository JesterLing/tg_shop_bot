import React, { useState, useMemo, useCallback } from 'react';
import { useSelector } from 'react-redux';

import Layout from '../../components/Layout/Layout';
import useTitle from '../../hooks/useTitle';
import DataTable from '../../components/DataTable/DataTable';
import Alert from '../../components/UI/Alert';
import Modal from '../../components/UI/Modal';
import { useGetPurchasesQuery } from '../../service/API';
import { collectUserName, formatTimesmap } from '../../components/Utils';
import DetailsSubRow from './DetailsSubRow';

import styles from '../../components/DataTable/DataTable.module.less';

const Purchases = () => {
  const { type, currency } = useSelector((state) => state.global.configs);
  useTitle('Покупки');

  const { data, isLoading, isError, error } = useGetPurchasesQuery();
  const [contentModal, setContentModal] = useState({ visible: false, content: '' });

  const columns = useMemo(() => {
    const data1 = [
      {
        Header: () => null,
        id: 'expander',
        Cell: ({ row }) => (
          <span
            {...row.getToggleRowExpandedProps({
              onClick: (e) => {
                e.stopPropagation();
                row.toggleRowExpanded();
              },
              className: row.isExpanded
                ? styles.expand + ' ' + styles.close
                : styles.expand + ' ' + styles.open
            })}></span>
        )
      },
      {
        Header: '#ID',
        accessor: 'id'
      },
      {
        Header: 'Пользователь',
        accessor: (row) => row.user.user_id,
        Cell: ({ row, value }) => {
          let name = collectUserName({
            username: row.original.user.username,
            first_name: row.original.user.first_name,
            last_name: row.original.user.last_name
          });
          return (
            <a href={`tg://user?id=${value}`} target="_blank" title={value}>
              {name}
            </a>
          );
        }
      },
      {
        Header: 'Общая цена',
        accessor: 'sum',
        Cell: ({ value }) => value + ' ' + currency + '.'
      },
      {
        Header: 'Система',
        accessor: (row) => row.payment.service
      },
      {
        Header: 'Статус',
        accessor: (row) => row.payment.status,
        Cell: ({ value }) => {
          switch (value) {
            case 0:
              return '';
            case 1:
              return <span className="label accent">Ожидание</span>;
            case 2:
              return <span className="label okay">Оплачено</span>;
            case 3:
              return <span className="label accent">Обработка</span>;
            case 4:
              return <span className="label">Истек</span>;
            case 5:
              return <span className="label danger">Отклонено</span>;
            case 6:
              return <span className="label warning">Отменено</span>;
            case 7:
              return <span className="label danger">Ошибка</span>;
            case 8:
              return <span className="label">Неизвестно</span>;
          }
        }
      },
      {
        Header: 'Дата',
        accessor: 'date',
        Cell: ({ value }) => formatTimesmap(value),
        sortType: 'alphanumeric'
      }
    ];
    const data2 = [
      {
        Header: 'Доставка',
        accessor: 'delivery',
        Cell: ({ value }) => (value === null ? '-' : value ? 'Адрес' : 'Самовывоз')
      },
      {
        Header: 'Адрес',
        accessor: 'address',
        Cell: ({ value }) => (value === null ? '-' : value)
      },
      {
        Header: 'Номер',
        accessor: 'number'
      }
    ];
    return type ? data1 : [...data1, ...data2];
  }, []);

  const getRowId = (row, relativeIndex, parent) => {
    return parent ? [parent.id, row.id].join('.') : row.id;
  };

  const renderRowSubComponent = useCallback(
    ({ row, rowProps, visibleColumns }) => (
      <DetailsSubRow
        row={row}
        rowProps={rowProps}
        visibleColumns={visibleColumns}
        setContentModal={setContentModal}
      />
    ),
    []
  );

  return (
    <Layout>
      {isError && (
        <Alert type="danger" icon={true}>
          {error.data.message}
        </Alert>
      )}
      {isLoading ? (
        <span className="spinner spinner-demo" id="load-main">
          <span></span>
        </span>
      ) : (
        <section>
          <Modal
            visible={{
              get: contentModal.visible,
              set: (val) => setContentModal({ ...contentModal, visible: val })
            }}>
            {contentModal.content}
          </Modal>
          {!data.length ? (
            <p className="mt-20 mh-5">Похоже что покупок еще нет</p>
          ) : (
            <DataTable
              columns={columns}
              data={data}
              getRowId={getRowId}
              group={() => {}}
              renderRowSubComponent={renderRowSubComponent}
            />
          )}
        </section>
      )}
    </Layout>
  );
};

export default Purchases;
