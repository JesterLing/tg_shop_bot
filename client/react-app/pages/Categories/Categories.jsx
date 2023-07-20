import React, { useState, useEffect, Fragment } from 'react';
import { useForm, Controller } from 'react-hook-form';
import * as yup from 'yup';
import { yupResolver } from '@hookform/resolvers/yup';
import Nestable from 'react-nestable';

import Layout from '../../components/Layout/Layout';
import Alert from '../../components/UI/Alert';
import Button from '../../components/UI/Button';
import LoadingButton from '../../components/UI/LoadingButton';
import Modal from '../../components/UI/Modal';
import Input from '../../components/UI/Input';
import Checkbox from '../../components/UI/Checkbox';
import useTitle from '../../hooks/useTitle';
import {
  useGetCategoriesQuery,
  useAddCategoryMutation,
  useOrderCategoryMutation,
  useEditCategoryMutation,
  useDeleteCategoryMutation
} from '../../service/API';

import 'react-nestable/dist/styles/index.css';
import './Categories.less';

const CategoriesPage = () => {
  useTitle('Категории');
  const { data, isLoading, isError, error } = useGetCategoriesQuery();
  const [
    add,
    { isLoading: isAddLoading, isUninitialized: isAddUnit, isError: isAddError, error: addError }
  ] = useAddCategoryMutation();
  const [order, { isLoading: isOrderLoading, isError: isOrderError, error: orderError }] =
    useOrderCategoryMutation();
  const [
    edit,
    {
      isLoading: isEditLoading,
      isUninitialized: isEditUnit,
      isError: isEditError,
      error: editError
    }
  ] = useEditCategoryMutation();
  const [del, { isLoading: isDelLoading, isError: isDelError, error: delError }] =
    useDeleteCategoryMutation();

  const [modalVisible, setModalVisible] = useState(false);

  const schema = yup.object().shape({
    id: yup.number().nullable(),
    name: yup.string().required('Имя обязательное поле').min(3, 'Мининум 3 символа'),
    hide: yup.boolean()
  });

  const { control, setValue, handleSubmit, register, reset } = useForm({
    defaultValues: { id: null, name: '', hide: false },
    mode: 'onChange',
    resolver: yupResolver(schema)
  });

  const [isRequestAnimationComplite, setRequestAnimationComplite] = useState(false);

  useEffect(() => {
    if (!isAddError && !isEditError && (!isAddUnit || !isEditUnit)) {
      setRequestAnimationComplite(true);
      const ftimer = setTimeout(() => {
        setRequestAnimationComplite(false);
        setModalVisible(false);
      }, 1000);
      return () => clearTimeout(ftimer);
    }
  }, [isAddLoading, isEditLoading]);

  const onSubmitHandler = (data) => {
    data.id ? edit(data) : add(data);
  };

  const renderCategory = ({ item, collapseIcon }) => {
    return (
      <div className="item">
        <div>
          {collapseIcon}
          <span>{item.name}</span>
        </div>
        <div>
          <Button
            text=""
            color="blue"
            icon="pencil"
            compact={true}
            onClick={() => {
              setValue('id', item.id);
              setValue('name', item.name);
              setValue('hide', item.hide);
              setModalVisible(true);
            }}
          />
          <Button
            text=""
            color="red"
            icon="trash"
            compact={true}
            onClick={() => del({ id: item.id })}
          />
        </div>
      </div>
    );
  };

  const renderModalButtons = () => {
    return (
      <Fragment>
        <Button text="Отменить" color="gray" hollow={true} onClick={() => setModalVisible(false)} />
        <LoadingButton
          text="Сохранить"
          color="green"
          icon="save"
          className="align-right"
          type="submit"
          loading={isAddLoading || isEditLoading}
          success={isRequestAnimationComplite}
        />
      </Fragment>
    );
  };

  return (
    <Layout>
      {isError && (
        <Alert type="danger" icon={true}>
          {error.data.message}
        </Alert>
      )}
      {isOrderError && (
        <Alert type="danger" icon={true}>
          {orderError.data.message}
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
          <form id="category_form" onSubmit={handleSubmit(onSubmitHandler)}>
            <Modal
              header="Добавление/редактирование категории"
              visible={{ get: modalVisible, set: setModalVisible }}
              buttons={renderModalButtons}>
              {(isAddError || isEditError) && (
                <Alert type="danger" icon={true}>
                  {addError}
                  {editError}
                </Alert>
              )}
              <input name="id" type="hidden" {...register('id')} />
              <Controller
                name="name"
                control={control}
                render={({ field, fieldState: { error } }) => (
                  <Input id="cat_name" label="Имя категории" error={error?.message} {...field} />
                )}
              />
              <Controller
                name="hide"
                control={control}
                render={({ field: { value, onChange } }) => (
                  <Checkbox
                    id="cat_hide"
                    label="Скрыть"
                    checked={value}
                    caption="Можно скрыть категорию и все дочерние товары на время не удаляя"
                    onChange={(e) => onChange(e.target.checked)}
                  />
                )}
              />
            </Modal>
          </form>
          <Button
            text="Добавить"
            color="blue"
            onClick={() => {
              reset();
              setModalVisible(true);
            }}
          />
          {!data.length ? (
            <p className="mt-20 mh-5">
              Похоже категорий еще нет. Начните прямо сейчас создав новую!
            </p>
          ) : (
            <Nestable
              items={data}
              renderItem={renderCategory}
              onChange={({ items }) => order(items)}
              collapsed={false}
            />
          )}
        </section>
      )}
    </Layout>
  );
};

export default CategoriesPage;
