import React, { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import * as yup from 'yup';
import { yupResolver } from '@hookform/resolvers/yup';

import { useSetFirstStartMutation } from '../../service/API';
import LoadingButton from '../../components/UI/LoadingButton';
import Alert from '../../components/UI/Alert';
import Button from '../../components/UI/Button';
import Radio from '../../components/UI/Radio';

const StepThree = ({ fill, prevStep }) => {
  const [exec, { isLoading, isError, error }] = useSetFirstStartMutation();
  const navigate = useNavigate();
  const { register, handleSubmit, reset } = useForm({
    resolver: yupResolver(
      yup.object().shape({
        type: yup.boolean().required()
      })
    )
  });

  useEffect(() => {
    reset({ type: String(fill.data.type) });
  }, [fill]);

  const onSubmitHandler = (data) => {
    data = { ...data, step: 3 };
    exec(data)
      .unwrap()
      .then((result) => {
        if (result.type == 'redirect') {
          navigate(`${result.url ? result.url : '/'}`);
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
        <div className="row mb-20">
          <div className="col-2">
            <Radio
              id="digital"
              label="Бот цифровых товаров (без корзины)"
              value="1"
              {...register('type')}
            />
            <label htmlFor="digital" style={{ cursor: 'pointer' }}>
              Например: ключи, аккаунты, логи, курсы, все что можно выдать в виде текста или файла
            </label>
          </div>
          <div className="col-2">
            <Radio
              id="real"
              label="Бот физических товаров (с корзиной)"
              value="0"
              {...register('type')}
            />
            <label htmlFor="real" style={{ cursor: 'pointer' }}>
              Например: табак, пицца, суши, все что можно доставлять или забрать самовывозом
            </label>
          </div>
        </div>
        <div style={{ display: 'flex', justifyContent: 'space-between' }}>
          <Button text="Назад" color="red" hollow={true} onClick={prevStep} />
          <LoadingButton
            text="Сохранить"
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

export default StepThree;
