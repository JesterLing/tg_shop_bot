import React, { useState, useEffect, useMemo, Fragment } from 'react';
import { useNavigate } from 'react-router-dom';
import { useSelector } from 'react-redux';

import Layout from '../../components/Layout/Layout';
import useTitle from '../../hooks/useTitle';
import Alert from '../../components/UI/Alert';
import Button from '../../components/UI/Button';
import Switch from '../../components/UI/Switch';
import DataTable from '../../components/DataTable/DataTable';
import { useGetGoodsQuery, useDelProductMutation } from '../../service/API';

const GoodsPage = () => {
  const { currency } = useSelector((state) => state.global.configs);
  useTitle('Товары');
  const navigate = useNavigate();

  const { data, isLoading, isError, error } = useGetGoodsQuery();
  const [deleteGoods, { isLoading: isDelLoading, isError: isDelError, error: delError }] =
    useDelProductMutation();

  const [selectedRows, setSelectedRows] = useState([]);
  const [groupByCat, setGroupByCat] = useState(false);
  const [warning, setWarning] = useState(null);

  const addHandler = (e) => {
    e.preventDefault();
    navigate('/goods/new');
  };

  const editHandler = (e) => {
    if (!selectedRows || selectedRows.length === 0) {
      setWarning('Не выбран ни один продукт');
    } else if (selectedRows.length > 1) {
      setWarning('Для редактирования можно выбрать только один продукт');
    } else {
      e.preventDefault();
      navigate(`/goods/${selectedRows[0]?.id}`, { replace: true });
    }
  };

  const deleteHandler = () => {
    if (selectedRows.length < 1) {
      setWarning('Не выбран ни один продукт');
      return;
    }
    let ids = selectedRows.map((o) => o.id);
    deleteGoods(ids);
  };

  useEffect(() => {
    if (warning) {
      const timer = setTimeout(() => {
        setWarning(null);
      }, 4000);
      return () => clearTimeout(timer);
    }
  }, [warning]);

  const getRowId = (row, relativeIndex, parent) => {
    return parent ? [parent.id, row.id].join('.') : row.id;
  };

  const columns = useMemo(
    () => [
      {
        Header: 'Продукт',
        accessor: 'pname',
        Cell: ({ row, value }) => {
          if (row.original.image !== null) {
            return (
              <Fragment>
                <img
                  src={row.original.image}
                  style={{
                    verticalAlign: 'middle',
                    marginRight: '8px',
                    width: '40px',
                    height: '30px'
                  }}
                  alt=""
                />{' '}
                {value}
              </Fragment>
            );
          } else {
            return value;
          }
        },
        Aggregated: () => ' '
      },
      {
        Header: 'Категория',
        accessor: 'cname'
      },
      {
        Header: 'Цена',
        accessor: 'price',
        Cell: ({ value }) => value + ' ' + currency + '.',
        aggregate: 'average',
        Aggregated: ({ value }) => `Сред: ${Math.round(value * 100) / 100} ${currency}`
      },
      {
        Header: 'Количество',
        accessor: 'quantity',
        Cell: ({ value }) => (value ? value + ' шт.' : '-'),
        aggregate: 'sum',
        Aggregated: ({ value }) => `Всего: ${value}`
      },
      {
        Header: 'Скидка',
        accessor: 'dis_percent',
        Cell: ({ row, value }) => {
          if (value != null) {
            if (row.original.discount !== 0) {
              return '> ' + row.original.discount + 'шт ' + value + '%';
            } else {
              return `${value}%`;
            }
          } else {
            return 'Нет';
          }
        },
        aggregate: 'average',
        Aggregated: ({ value }) => `Сред: ${Math.round(value * 100) / 100}%`
      },
      {
        Header: 'Скрыт',
        accessor: 'hide',
        Cell: ({ value }) => (value ? 'Да' : 'Нет'),
        aggregate: (values) => {
          let yes = 0;
          let no = 0;
          values.forEach((value) => (value ? yes++ : no++));
          return [yes, no];
        },
        Aggregated: ({ value }) => `Да (${value[0]}) Нет (${value[1]})`
      }
    ],
    []
  );

  const groupSwitch = (toggleGroupBy, toggleAllRowsExpanded) => (
    <Switch
      labelLeft="Групировка по категории"
      id="table_group"
      onChange={(e) => {
        toggleGroupBy('cname', !groupByCat);
        setGroupByCat(!groupByCat);
      }}
      checked={groupByCat}
    />
  );

  return (
    <Layout>
      {warning && (
        <Alert type="warning" icon={true} popup={true}>
          {warning}
        </Alert>
      )}
      {isError && (
        <Alert type="danger" icon={true}>
          {error.data.message}
        </Alert>
      )}
      {isDelError && (
        <Alert type="danger" icon={true}>
          {delError.data.message}
        </Alert>
      )}
      {isLoading ? (
        <span className="spinner spinner-demo" id="load-main">
          <span></span>
        </span>
      ) : (
        <section>
          <div style={{ display: 'flex', gap: '5px' }}>
            <Button text="Добавить" color="blue" icon="plus" onClick={addHandler}></Button>
            <Button
              text="Редактировать"
              color="blue"
              icon="pencil"
              onClick={editHandler}
              disabled={!data.length}></Button>
            <Button
              text="Удалить"
              color="red"
              icon="trash"
              onClick={deleteHandler}
              disabled={!data.length}></Button>
          </div>
          {!data.length ? (
            <p className="mt-20 mh-5">Похоже что товаров еще нет</p>
          ) : (
            <DataTable
              columns={columns}
              data={data}
              selectedRows={selectedRows}
              setSelectedRows={setSelectedRows}
              group={groupSwitch}
              getRowId={getRowId}
            />
          )}
        </section>
      )}
    </Layout>
  );
};

export default GoodsPage;
