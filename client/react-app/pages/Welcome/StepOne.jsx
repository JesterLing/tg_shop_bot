import React, { useEffect } from 'react';
import { useForm, Controller } from 'react-hook-form';
import * as yup from 'yup';
import { yupResolver } from '@hookform/resolvers/yup';

import { useSetFirstStartMutation } from '../../service/API';
import Input from '../../components/UI/Input';
import LoadingButton from '../../components/UI/LoadingButton';
import Alert from '../../components/UI/Alert';

const StepOne = ({ fill, nextStep }) => {
  const [exec, { isLoading, isError, error }] = useSetFirstStartMutation();

  const { control, handleSubmit, reset } = useForm({
    mode: 'onChange',
    resolver: yupResolver(
      yup.object().shape({
        api_key: yup
          .string()
          .required('Обязательное поле')
          .matches(/[0-9]{8,10}:[a-zA-Z0-9_-]{35}/, {
            message:
              'Неверный формат токена. Пример токена 644739147:AAGMPo-Jz3mKRnHRTnrPEDi7jUF1vqNOD5k'
          }),
        bot_username: yup
          .string()
          .required('Обязательное поле')
          .matches(/^(?![@\s])/, 'Неверный формат имени')
      })
    )
  });

  useEffect(() => {
    reset(fill.data);
  }, [fill]);

  const onSubmitHandler = (data) => {
    data = { ...data, step: 1 };
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
          {error?.data.message}
        </Alert>
      )}
      <form onSubmit={handleSubmit(onSubmitHandler)}>
        <Controller
          name="bot_username"
          control={control}
          render={({ field: { value, ...rest }, fieldState: { error } }) => (
            <Input
              id="bot_username"
              label="Имя телеграм бота"
              addon="@"
              value={value || ''}
              error={error?.message}
              {...rest}
            />
          )}
        />
        <Controller
          name="api_key"
          control={control}
          render={({ field: { value, ...rest }, fieldState: { error } }) => (
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
              value={value || ''}
              error={error?.message}
              {...rest}
            />
          )}
        />
        <div style={{ display: 'flex', justifyContent: 'end' }}>
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

export default StepOne;
