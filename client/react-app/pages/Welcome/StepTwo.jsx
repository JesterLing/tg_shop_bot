import React, { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import * as yup from 'yup';
import { yupResolver } from '@hookform/resolvers/yup';

import { useSetFirstStartMutation } from '../../service/API';
import { collectUserName } from '../../components/Utils';
import Checkbox from '../../components/UI/Checkbox';
import LoadingButton from '../../components/UI/LoadingButton';
import Alert from '../../components/UI/Alert';
import Button from '../../components/UI/Button';

const StepTwo = ({ fill, isFetching, prevStep, nextStep }) => {
  const [exec, { isLoading, isError, error }] = useSetFirstStartMutation();

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors }
  } = useForm({
    resolver: yupResolver(
      yup.object().shape({
        admins: yup.array().of(yup.number()).min(1, 'Нужно выбрать минимум одного администратора')
      })
    )
  });

  useEffect(() => {
    const filtered = fill.data.users?.flatMap(({ id, is_admin }) => (is_admin ? String(id) : []));
    reset({ admins: filtered });
  }, [fill]);

  const onSubmitHandler = (data) => {
    data = { ...data, step: 2 };
    exec(data)
      .unwrap()
      .then((result) => {
        if (result.type == 'success') {
          nextStep();
        }
      });
  };

  return (
    <>
      {isError && (
        <Alert type="danger" icon={true}>
          {error?.data?.message}
        </Alert>
      )}
      <form onSubmit={handleSubmit(onSubmitHandler)}>
        <p>
          Выберете администраторов которые будут иметь доступ к админке. Для того чтобы пользователь
          появился здесь нужно зарегистрироваться в боте. Для этого достаточно например зайти в бота
          и нажать запустить или ввести команду /start
        </p>
        {errors.admins && <p className="text-red">{errors.admins.message}</p>}
        {isFetching ? (
          <p>
            <span className="spinner spinning">
              <span></span>
            </span>
            &nbsp;Обновление списка...
          </p>
        ) : fill.data.users ? (
          fill.data.users.map((user) => (
            <Checkbox
              key={user.id}
              id={`user_${user.id}`}
              value={user.id}
              label={collectUserName(user, true)}
              {...register('admins')}
            />
          ))
        ) : (
          <p>Пока нет ни одного пользователя. Обновляем каждые 5 сек.</p>
        )}
        <div style={{ display: 'flex', justifyContent: 'space-between' }}>
          <Button text="Назад" color="red" hollow={true} onClick={prevStep} />
          <LoadingButton
            text="Далее"
            color="green"
            type="submit"
            loading={isLoading}
            success={false}
          />
        </div>
      </form>
    </>
  );
};

export default StepTwo;
