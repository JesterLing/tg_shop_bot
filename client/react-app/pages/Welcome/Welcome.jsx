import React, { useMemo, useState } from 'react';
import { Navigate } from 'react-router-dom';

import { useFirstStartQuery } from '../../service/API';
import Alert from '../../components/UI/Alert';

import useTitle from '../../hooks/useTitle';
import StepOne from './StepOne';
import StepTwo from './StepTwo';
import StepThree from './StepThree';

import './Welcome.less';

const Welcome = () => {
  useTitle('Первоначальная настройки бота');
  const [step, setStep] = useState(1);
  const { data, isLoading, isFetching, isError, error } = useFirstStartQuery(step, {
    pollingInterval: step == 2 ? 5000 : 0
  });

  const nextStep = () => {
    setStep(step + 1);
  };

  const prevStep = () => {
    setStep(step - 1);
  };

  const Steps = useMemo(
    () => [
      { label: 'Имя и токен', form: <StepOne fill={data} nextStep={nextStep} /> },
      {
        label: 'Администратор',
        form: (
          <StepTwo fill={data} isFetching={isFetching} prevStep={prevStep} nextStep={nextStep} />
        )
      },
      { label: 'Тип бота', form: <StepThree fill={data} prevStep={prevStep} /> }
    ],
    [data, isFetching]
  );

  if (!isLoading && !isError) {
    if (data?.type == 'redirect') {
      return <Navigate to={`${data.url ? data.url : '/'}`} />;
    }
  }

  return (
    <div className="container welcome-container">
      <div className="welcome-center">
        <h2>Приветствую смотрящих, приступим к настройке!</h2>
        {isError && (
          <Alert type="danger" icon={true}>
            {error?.data?.message}
          </Alert>
        )}
        <ul className="form-stepper">
          {Steps.map((st, i) => (
            <li className={`${step > i + 1 ? 'finish' : step == i + 1 ? 'active' : ''}`} key={i}>
              <span className="form-stepper-circle">
                <span>{i + 1}</span>
              </span>
              <div className="label">{st.label}</div>
            </li>
          ))}
        </ul>
        {isLoading && step != 2 ? (
          <span className="spinner spinning">
            <span></span>
          </span>
        ) : (
          Steps.at(step - 1).form
        )}
      </div>
    </div>
  );
};

export default Welcome;
