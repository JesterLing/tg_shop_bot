import React, { Fragment, useEffect, useState } from 'react';
import { useForm, Controller } from 'react-hook-form';
import * as yup from 'yup';
import { yupResolver } from '@hookform/resolvers/yup';

import Layout from '../../components/Layout/Layout';
import useTitle from '../../hooks/useTitle';
import Alert from '../../components/UI/Alert';
import Checkbox from '../../components/UI/Checkbox';
import Input from '../../components/UI/Input';
import LoadingButton from '../../components/UI/LoadingButton';
import Select from '../../components/UI/Select';
import Switch from '../../components/UI/Switch';
import Tab from '../../components/UI/Tab';
import TabsWrapper from '../../components/UI/TabsWrapper';
import { useGetSettingsQuery, useSetSettingsMutation } from '../../service/API';
import { useActions } from '../../hooks/useActions';
import AdminsEdit from './AdminsEdit';

const SettingsPage = () => {
  useTitle('Настройки');
  const { setConfigs } = useActions();
  const { data, isLoading, isError, error } = useGetSettingsQuery();
  const [
    save,
    {
      isLoading: isSaveLoading,
      isUninitialized: isSaveInit,
      isError: isSaveError,
      error: saveError
    }
  ] = useSetSettingsMutation();

  const schema = yup.object().shape({
    api_key: yup.string().required(),
    bot_username: yup.string().required(),
    currency: yup.number().required(),
    greetings: yup.string().required(),
    success_order: yup.string().required(),
    type: yup.bool().required(),
    service: yup.bool().required(),
    pay_coinbase: yup.bool().required(),
    pay_crystalpay: yup.bool().required(),
    pay_delivery: yup.bool().required(),
    pay_qiwi: yup.bool().required(),
    //coinbase_key: yup.string().nullable().when('pay_coinbase', { is: true, then: (schema) => schema.required() }),
    crystalpay_login: yup
      .string()
      .nullable()
      .when('pay_crystalpay', { is: true, then: (schema) => schema.required() }),
    crystalpay_key: yup
      .string()
      .nullable()
      .when('pay_crystalpay', { is: true, then: (schema) => schema.required() }),
    qiwi_private_key: yup
      .string()
      .nullable()
      .when('pay_qiwi', { is: true, then: (schema) => schema.required() })
  });

  const {
    control,
    handleSubmit,
    reset,
    watch,
    formState: { errors, touchedFields }
  } = useForm({
    defaultValues: {
      api_key: '',
      bot_username: '',
      currency: 0,
      greetings: '',
      success_order: '',
      type: '0',
      service: 0,
      pay_coinbase: 0,
      pay_crystalpay: 0,
      pay_delivery: 0,
      pay_qiwi: 0,
      // coinbase_key: '',
      crystalpay_login: '',
      crystalpay_key: '',
      qiwi_private_key: ''
    },
    mode: 'onChange',
    resolver: yupResolver(schema)
  });

  const watchType = watch('type');
  const [typeChange, setTypeChange] = useState({
    api_key: 'password',
    coinbase_key: 'password',
    crystalpay_key: 'password',
    qiwi_private_key: 'password'
  });
  const [isSaveAnimationComplite, setSaveAnimationComplite] = useState(false);

  const onSubmitHandler = (data) => {
    save(data)
      .unwrap()
      .then((result) => {
        if (result.type == 'success' && result.update) {
          setConfigs(result.update);
        }
      });
  };
  useEffect(() => {
    if (!isSaveError && !isSaveInit) {
      setSaveAnimationComplite(true);
      const ftimer = setTimeout(() => {
        setSaveAnimationComplite(false);
      }, 1000);
      return () => clearTimeout(ftimer);
    }
  }, [isSaveLoading]);

  useEffect(() => {
    if (!isError) {
      reset(data);
    }
  }, [isLoading]);

  return (
    <Layout>
      {isError && (
        <Alert type="danger" icon={true}>
          {error.data.message}
        </Alert>
      )}
      {isSaveError && (
        <Alert type="danger" icon={true}>
          {saveError.data.message}
        </Alert>
      )}
      {isLoading ? (
        <span className="spinner spinning">
          <span></span>
        </span>
      ) : (
        <section>
          <form onSubmit={handleSubmit(onSubmitHandler)}>
            <div className="row p-15">
              <div className="col-2 p-10">
                <Controller
                  name="type"
                  control={control}
                  render={({ field: { onChange, value, onBlur } }) => (
                    <input
                      className="radiobox"
                      type="radio"
                      value="1"
                      id="modeDigital"
                      checked={value ? true : false}
                      onChange={(e) => onChange(true)}
                      onBlur={onBlur}
                    />
                  )}
                />
                <label htmlFor="modeDigital">
                  Бот цифровых товаров (без корзины)
                  <br />
                  Например: ключи, аккаунты, логи, курсы, все что можно выдать в виде текста или
                  файла
                </label>
              </div>
              <div className="col-2 p-10">
                <Controller
                  name="type"
                  control={control}
                  render={({ field: { onChange, value, onBlur } }) => (
                    <input
                      className="radiobox"
                      type="radio"
                      value="0"
                      id="modeReal"
                      checked={value ? false : true}
                      onChange={(e) => onChange(false)}
                      onBlur={onBlur}
                    />
                  )}
                />
                <label htmlFor="modeReal">
                  Бот физических товаров (с корзиной)
                  <br />
                  Например: табак, пицца, суши, все что можно доставлять или забрать самовывозом
                </label>
              </div>
            </div>
            <TabsWrapper style="tab-style-02">
              <Tab label="Основные" icon="cog">
                <Controller
                  name="api_key"
                  control={control}
                  render={({ field: { onChange, value, onBlur }, fieldState: { error } }) => (
                    <Input
                      id="api_key"
                      label="Токен телеграм бота"
                      caption={
                        <span>
                          Узнать как получить можно{' '}
                          <a
                            href="https://core.telegram.org/bots/features#creating-a-new-bot"
                            target="_blank">
                            здесь
                          </a>
                        </span>
                      }
                      type={typeChange.api_key}
                      error={error?.message}
                      value={atob(value)}
                      onChange={(e) => {
                        onChange(btoa(e.target.value));
                      }}
                      onBlur={(e) => {
                        setTypeChange({ ...typeChange, api_key: 'password' });
                        onBlur();
                      }}
                      onFocus={(e) => setTypeChange({ ...typeChange, api_key: 'text' })}
                    />
                  )}
                />
                <Controller
                  name="bot_username"
                  control={control}
                  render={({ field: { ref, ...rest }, fieldState: { error } }) => (
                    <Input
                      id="bot_username"
                      label="Имя телеграм бота"
                      addon="@"
                      error={error?.message}
                      {...rest}
                    />
                  )}
                />
                <Controller
                  name="service"
                  control={control}
                  render={({ field: { value, onChange } }) => (
                    <Switch
                      id="service"
                      labelTop="Поставить бота на обслуживание"
                      labelRight="Обслуживание"
                      onChange={(e) => onChange(e.target.checked)}
                      checked={value}
                    />
                  )}
                />
                <div className="from-group">
                  <label className="form-label" htmlFor="currency">
                    Валюта
                  </label>
                  <Controller
                    name="currency"
                    control={control}
                    render={({ field: { onChange, onBlur, value } }) => (
                      <Select
                        id="category"
                        options={[
                          { value: 980, label: 'грн (UAH)' },
                          { value: 643, label: 'руб (RUB)' },
                          { value: 840, label: '$ (USD)' }
                        ]}
                        selected={value}
                        callback={(e) => {
                          onChange(Number(e));
                          onBlur();
                        }}
                      />
                    )}
                  />
                  <p className="form-caption">
                    Если у вас уже есть товары или покупки они будут сконвертированы относительно
                    текущего курса. Источники курса API Tinkoff, Privatbank. Операция конвертации
                    может занять некоторое время.{' '}
                  </p>
                </div>
                <AdminsEdit />
              </Tab>
              <Tab label="Методы оплаты" icon="credit-card">
                {!watchType ? (
                  <Fragment>
                    <h5 className="mt-20">Оплата при получении</h5>
                    <Controller
                      name="pay_delivery"
                      control={control}
                      render={({ field: { value, onChange } }) => (
                        <Checkbox
                          id="cashPayment"
                          label="Включить"
                          checked={value}
                          onChange={(e) => onChange(e.target.checked)}
                        />
                      )}
                    />
                  </Fragment>
                ) : (
                  ''
                )}
                <h5 className="mt-20">QIWI</h5>
                <Controller
                  name="pay_qiwi"
                  control={control}
                  render={({ field: { value, onChange } }) => (
                    <Checkbox
                      id="qiwiPayment"
                      label="Включить"
                      checked={value}
                      onChange={(e) => onChange(e.target.checked)}
                    />
                  )}
                />
                <Controller
                  name="qiwi_private_key"
                  control={control}
                  render={({ field: { onChange, value, onBlur }, fieldState: { error } }) => (
                    <Input
                      id="qiwiKey"
                      placeholder="Key"
                      type={typeChange.qiwi_private_key}
                      error={error?.message}
                      value={atob(value)}
                      onChange={(e) => {
                        onChange(btoa(e.target.value));
                      }}
                      onBlur={(e) => {
                        setTypeChange({ ...typeChange, qiwi_private_key: 'password' });
                        onBlur();
                      }}
                      onFocus={(e) => setTypeChange({ ...typeChange, qiwi_private_key: 'text' })}
                    />
                  )}
                />
                <h5 className="mt-10">Crystalpay</h5>
                <Controller
                  name="pay_crystalpay"
                  control={control}
                  render={({ field: { value, onChange } }) => (
                    <Checkbox
                      id="crystalPayment"
                      label="Включить"
                      checked={value}
                      onChange={(e) => onChange(e.target.checked)}
                    />
                  )}
                />
                <Controller
                  name="crystalpay_login"
                  control={control}
                  render={({ field: { ref, ...rest }, fieldState: { error } }) => (
                    <Input
                      id="crystalpayLogin"
                      placeholder="Login"
                      error={error?.message}
                      {...rest}
                    />
                  )}
                />
                <Controller
                  name="crystalpay_key"
                  control={control}
                  render={({ field: { onChange, value, onBlur }, fieldState: { error } }) => (
                    <Input
                      id="crystalpay_key"
                      placeholder="Key"
                      type={typeChange.crystalpay_key}
                      error={error?.message}
                      value={atob(value)}
                      onChange={(e) => {
                        onChange(btoa(e.target.value));
                      }}
                      onBlur={(e) => {
                        setTypeChange({ ...typeChange, crystalpay_key: 'password' });
                        onBlur();
                      }}
                      onFocus={(e) => setTypeChange({ ...typeChange, crystalpay_key: 'text' })}
                    />
                  )}
                />
              </Tab>
              <Tab label="Интерфейс" icon="credit-tag">
                <div className="form-group">
                  <label className="form-label" htmlFor="greetings">
                    Текст приветствия после запуска бота (/start)
                  </label>
                  <Controller
                    name="greetings"
                    control={control}
                    render={({ field }) => (
                      <textarea id="description" className="input" rows={3} {...field}></textarea>
                    )}
                  />
                </div>
                <div className="form-group">
                  <label className="form-label" htmlFor="success_order">
                    Текст после успешно совершенной покупки
                  </label>
                  <Controller
                    name="success_order"
                    control={control}
                    render={({ field }) => (
                      <textarea id="description" className="input" rows={3} {...field}></textarea>
                    )}
                  />
                </div>
              </Tab>
            </TabsWrapper>
            <LoadingButton
              text="Сохранить"
              color="green"
              icon="save"
              className="align-right"
              type="submit"
              loading={isSaveLoading}
              success={isSaveAnimationComplite}
            />
          </form>
        </section>
      )}
    </Layout>
  );
};

export default SettingsPage;
