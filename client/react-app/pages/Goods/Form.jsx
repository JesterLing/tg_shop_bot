import React, { useState, useEffect, Fragment, useMemo } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useSelector } from 'react-redux';
import { useForm, Controller } from 'react-hook-form';
import * as yup from 'yup';
import { yupResolver } from '@hookform/resolvers/yup';

import Layout from '../../components/Layout/Layout';
import useTitle from '../../hooks/useTitle';
import Alert from '../../components/UI/Alert';
import Button from '../../components/UI/Button';
import Radios from '../../components/UI/Radios';
import Checkbox from '../../components/UI/Checkbox';
import Select from '../../components/UI/Select';
import LoadingButton from '../../components/UI/LoadingButton';
import Input from '../../components/UI/Input';
import UploadImage from '../../components/Upload/UploadImage';
import UploadFiles from '../../components/Upload/UploadFiles';
import {
  useLazyGetCategoriesQuery,
  useLazyGetProductQuery,
  useAddProductMutation,
  useEditProductMutation
} from '../../service/API';

import styles from './Form.module.less';

const Form = () => {
  const { currency, type } = useSelector((state) => state.global.configs);
  useTitle('Создание новго товара');
  const navigate = useNavigate();
  const { id } = useParams();

  const schema = yup.object().shape(
    {
      name: yup.string().required('Имя товара обязательно').min(3, 'Минимум 3 символа'),
      description: yup.string().nullable(),
      category: yup
        .number()
        .required('Выбор категории обязателен')
        .notOneOf([0], 'Выбор категории обязателен'),
      price: yup
        .number()
        .typeError('Значение должно быть числом, можно использовать десятые')
        .required('Поле с ценой обязательно для заполнения'),
      image: yup
        .object()
        .shape({
          id: yup.number(),
          name: yup.string(),
          path: yup.string()
        })
        .transform((v, o) => (o == '' ? null : v))
        .nullable(),
      discount: yup
        .number()
        .typeError('Значение должно быть целым числом')
        .transform((v, o) => (o == '' ? null : v))
        .nullable()
        .when('dis_percent', {
          is: (val) => val != null,
          then: (schema) => schema.required('Так как указан процент, укажите количество')
        }),
      dis_percent: yup
        .number()
        .typeError('Значение должно быть целым числом')
        .transform((v, o) => (o == '' ? null : v))
        .nullable()
        .when('discount', {
          is: (val) => val != null,
          then: (schema) => schema.required('Так как указана скидка, укажите процент')
        }),
      hide: yup.boolean(),
      quantity: yup
        .number()
        .typeError('Значение должно быть числом')
        .transform((v, o) => (o == '' ? null : v))
        .nullable(),
      content_type: yup.string().nullable(),
      divider: yup
        .string()
        .nullable()
        .when('content_type', {
          is: 'TEXT_SEPARATED',
          then: (schema) => schema.required()
        }),
      content: yup.lazy((val) =>
        Array.isArray(val) ? yup.array().nullable() : yup.string().nullable()
      )
    },
    ['discount', 'dis_percent']
  );

  const {
    control,
    setValue,
    handleSubmit,
    formState: { errors, touchedFields },
    watch,
    reset
  } = useForm({
    defaultValues: {
      name: '',
      description: null,
      category: 0,
      price: '',
      image: null,
      discount: null,
      dis_percent: null,
      hide: false,
      quantity: null,
      content_type: null,
      divider: null,
      content: null
    },
    mode: 'onChange',
    resolver: yupResolver(schema)
  });

  const watchedFields = watch(['content_type', 'content', 'divider', 'quantity']);

  const [categories, setCategories] = useState([]);
  const [contentFind, setContentFind] = useState(null);
  const [successSave, setSuccessSave] = useState(false);

  const [getProduct, { isLoading: isProdLoading, isError: isProdError, error: prodError }] =
    useLazyGetProductQuery();
  const [getCategories, { isLoading: isCtgsLoading, isError: isCtgsError, error: ctgsError }] =
    useLazyGetCategoriesQuery();
  const [
    add,
    { isLoading: isAddLoading, isUninitialized: isAddInit, isError: isAddError, error: addError }
  ] = useAddProductMutation();
  const [
    edit,
    {
      isLoading: isEditLoading,
      isUninitialized: isEditInit,
      isError: isEditError,
      error: editError
    }
  ] = useEditProductMutation();

  const contentTypeRadios = useMemo(
    () => [
      { label: 'Текст статический', value: 'TEXT' },
      { label: 'Текст разделенный символом', value: 'TEXT_SEPARATED' },
      { label: 'Текст построчно', value: 'TEXT_LINES' },
      { label: 'Файл', value: 'FILE' }
    ],
    []
  );

  useEffect(() => {
    getCategories()
      .unwrap()
      .then((result) => {
        let ctgs = categoriesExtract(result);
        ctgs.unshift({
          value: 0,
          label: 'Выберите категорию',
          disabled: true
        });
        setCategories(ctgs);
      });
    if (id) {
      getProduct(id)
        .unwrap()
        .then((result) => {
          let sanitize = { ...result };
          if (sanitize.content_type == null) sanitize.content_type = 'TEXT';
          //for(let key in sanitize) if (sanitize[key] == null) sanitize[key] = '';
          reset(sanitize);
          //for(let key in sanitize) setValue(key, sanitize[key]);
        });
    }
  }, []);

  useEffect(() => {
    if (!isAddError && !isEditError && (!isAddInit || !isEditInit)) {
      setSuccessSave(true);
      const rTimer = setTimeout(() => {
        setSuccessSave(false);
        navigate('/goods', { replace: true });
      }, 1000);
      return () => clearTimeout(rTimer);
    }
  }, [isAddLoading, isEditLoading]);

  useEffect(() => {
    if (watchedFields[0] === 'TEXT_SEPARATED') {
      let lines = watchedFields[1].split(watchedFields[2]);
      if (!watchedFields[2] || !watchedFields[1] || lines.length < 2) {
        setContentFind(null);
      } else {
        setContentFind(lines.length);
      }
    }
    if (watchedFields[0] === 'TEXT_LINES') {
      let lines = watchedFields[1].split(/\r?\n/);
      if (watchedFields[1].length === 0 || lines.length < 1) {
        setContentFind(null);
      } else {
        setContentFind(lines.length);
      }
    }
  }, [watchedFields]);

  useEffect(() => {
    contentFind ? setValue('quantity', contentFind) : setValue('quantity', '');
  }, [contentFind]);

  useEffect(() => {
    const subscription = watch((value, { name, type }) => console.log(value, name, type));
    return () => subscription.unsubscribe();
  }, [watch]);
  useEffect(() => {
    console.log(errors);
  }, [touchedFields, errors]);

  const onSubmitHandler = (data) => {
    // const args = { ...data };
    // for (let key in args) if (args[key] === '') args[key] = null;

    if (data.id) {
      edit(data);
    } else {
      add(data);
    }
  };

  const categoriesExtract = (original, tranformed = Array(), depth = 1) => {
    original.forEach((item) => {
      tranformed.push({ value: item.id, label: '\u00A0\u00A0\u00A0'.repeat(depth) + item.name });
      if (item.children) {
        categoriesExtract(item.children, tranformed, depth + 1);
      }
    });
    return tranformed;
  };

  return (
    <Layout>
      {addError && (
        <Alert type="danger" icon={true}>
          {addError.data?.message}
        </Alert>
      )}
      {editError && (
        <Alert type="danger" icon={true}>
          {editError.data?.message}
        </Alert>
      )}
      {isCtgsError && (
        <Alert type="danger" icon={true}>
          {ctgsError.data?.message}
        </Alert>
      )}
      {isProdError && (
        <Alert type="danger" icon={true}>
          {prodError.data?.message}
        </Alert>
      )}
      {isCtgsLoading || isProdLoading ? (
        <span className="spinner spinner-demo" id="load-main">
          <span></span>
        </span>
      ) : (
        <section>
          <Button
            text="Назад"
            color="blue"
            hollow={true}
            icon="arrow-back"
            onClick={() => navigate('/goods', { replace: true })}></Button>
          <form className={styles.form} onSubmit={handleSubmit(onSubmitHandler)}>
            <Controller
              name="name"
              control={control}
              render={({ field, fieldState: { error } }) => (
                <Input id="name" label="Название продукта" error={error?.message} {...field} />
              )}
            />
            <div className={`from-group mb-20${errors.category ? ' validation error' : ''}`}>
              <label className="form-label" htmlFor="category">
                Категория
              </label>
              <Controller
                name="category"
                control={control}
                render={({ field: { onChange, onBlur, value } }) => (
                  <Select
                    id="category"
                    options={categories}
                    selected={value}
                    callback={(e) => {
                      onChange(Number(e));
                      onBlur();
                    }}
                  />
                )}
              />
              {errors.category && touchedFields.category && (
                <p className="form-caption" style={{ color: '#f05a67' }}>
                  {errors?.category?.message}
                </p>
              )}
            </div>
            <div className="form-group">
              <label className="form-label" htmlFor="description">
                Описание
              </label>
              <Controller
                name="description"
                control={control}
                render={({ field: { value, ...rest }, fieldState: { error } }) => (
                  <textarea
                    id="description"
                    className="input"
                    rows={8}
                    value={value || ''}
                    {...rest}></textarea>
                )}
              />
            </div>
            <div className="row mb-20">
              <div className="col-2">
                <Controller
                  name="price"
                  control={control}
                  render={({ field: { value, ...rest }, fieldState: { error } }) => (
                    <Input
                      id="price"
                      label="Цена"
                      caption={`Цену указывать в ${currency}`}
                      error={error?.message}
                      value={value || ''}
                      {...rest}
                    />
                  )}
                />
                <div className="row">
                  <div className="col-2">
                    <Controller
                      name="dis_percent"
                      control={control}
                      render={({ field: { value, ...rest }, fieldState: { error } }) => (
                        <Input
                          id="dis_percent"
                          label="Скидка (в %)"
                          error={error?.message}
                          value={value || ''}
                          {...rest}
                        />
                      )}
                    />
                  </div>
                  <div className="col-2">
                    <Controller
                      name="discount"
                      control={control}
                      render={({ field: { value, ...rest }, fieldState: { error } }) => (
                        <Input
                          id="discount"
                          label="Скидка от (в шт.)"
                          caption="Если указать 0 будет действовать на любое количество"
                          error={error?.message}
                          value={value || ''}
                          {...rest}
                        />
                      )}
                    />
                  </div>
                </div>
              </div>
              <div className="col-2">
                <Controller
                  name="image"
                  control={control}
                  render={({ field: { onChange, onBlur, value } }) => (
                    <UploadImage
                      image={value}
                      setImage={(e) => {
                        onChange(e);
                        onBlur();
                      }}
                    />
                  )}
                />
              </div>
            </div>
            <div className="mb-20">
              <Controller
                name="quantity"
                control={control}
                render={({ field: { value, ...rest }, fieldState: { error } }) => (
                  <Input
                    id="quantity"
                    label="Введите количество товара"
                    caption="Оставте пустым если количество не ограничено. Если товар закончиться позиция будет скрыта"
                    disabled={contentFind ? true : false}
                    error={error?.message}
                    value={value || ''}
                    {...rest}
                  />
                )}
              />
            </div>
            {!!type && (
              <div className="row">
                <div className={`col-1 ${styles.content}`}>
                  <div className="form-label">Контент</div>
                  <Controller
                    name="content_type"
                    control={control}
                    render={({ field: { onChange, value } }) => (
                      <Radios
                        options={contentTypeRadios}
                        selected={value}
                        onChange={(e) => {
                          onChange(e);
                          setValue('content', e.target.value === 'FILE' ? [] : '');
                          setContentFind(null);
                        }}
                      />
                    )}
                  />
                  {watchedFields[0] === 'TEXT' && (
                    <div className="form-group">
                      <label className="form-label" htmlFor="content_text">
                        Текст
                      </label>
                      <Controller
                        name="content"
                        control={control}
                        render={({ field: { value, ...rest }, fieldState: { error } }) => (
                          <textarea
                            id="content_text"
                            className="input"
                            cols={30}
                            rows={10}
                            value={value || ''}
                            {...rest}></textarea>
                        )}
                      />
                    </div>
                  )}
                  {watchedFields[0] == 'TEXT_SEPARATED' && (
                    <Fragment>
                      <Controller
                        name="divider"
                        control={control}
                        render={({ field: { value, ...rest }, fieldState: { error } }) => (
                          <Input
                            id="divider"
                            label="Разделитель"
                            error={error?.message}
                            value={value || ''}
                            {...rest}
                          />
                        )}
                      />
                      <div className="form-group">
                        <label className="form-label" htmlFor="content_text_separated">
                          Текст
                        </label>
                        <Controller
                          name="content"
                          control={control}
                          render={({ field: { value, ...rest }, fieldState: { error } }) => (
                            <textarea
                              id="content_text_separated"
                              className="input"
                              cols={30}
                              rows={10}
                              value={value || ''}
                              {...rest}></textarea>
                          )}
                        />
                        {contentFind && (
                          <p className="form-caption">Найдено {contentFind} фрагментов</p>
                        )}
                      </div>
                    </Fragment>
                  )}
                  {watchedFields[0] == 'TEXT_LINES' && (
                    <Fragment>
                      <div className="form-group">
                        <label className="form-label" htmlFor="content_text_lines">
                          Текст
                        </label>
                        <Controller
                          name="content"
                          control={control}
                          render={({ field: { value, ...rest }, fieldState: { error } }) => (
                            <textarea
                              id="content_text_lines"
                              className="input"
                              cols={30}
                              rows={10}
                              value={value || ''}
                              {...rest}></textarea>
                          )}
                        />
                        {contentFind && <p className="form-caption">Найдено {contentFind} строк</p>}
                      </div>
                    </Fragment>
                  )}
                  {watchedFields[0] === 'FILE' && (
                    <Controller
                      name="content"
                      control={control}
                      render={({ field: { onChange, onBlur, value } }) => (
                        <UploadFiles
                          files={value}
                          setFiles={(e) => {
                            onChange(e);
                            onBlur();
                          }}
                        />
                      )}
                    />
                  )}
                </div>
              </div>
            )}
            <div className="form-group">
              <Controller
                name="hide"
                control={control}
                render={({ field: { value, onChange } }) => (
                  <Checkbox
                    id="hide"
                    label="Скрытый товар"
                    checked={value}
                    onChange={(e) => onChange(e.target.checked)}
                  />
                )}
              />
            </div>
            <LoadingButton
              text="Сохранить"
              color="green"
              icon="save"
              className="align-right"
              type="submit"
              loading={isAddLoading || isEditLoading}
              success={successSave}
            />
          </form>
        </section>
      )}
    </Layout>
  );
};

export default Form;
