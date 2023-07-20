import React, { useEffect, useRef, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useSelector } from 'react-redux';

import useTitle from '../../hooks/useTitle';
import { useLoginBySecretMutation } from '../../service/API';
import { useActions } from '../../hooks/useActions';

import styles from './Login.module.less';

const LoginPage = () => {
  const { isAuth } = useSelector((state) => state.global);
  useTitle('Авторизация');
  const { auth, setConfigs } = useActions();
  const navigate = useNavigate();
  const { secret } = useParams();
  const timer = useRef(null);
  const [message, setMessage] = useState(
    'Для того чтобы войти в админку воспользуйтесь ботом и командой /admin'
  );
  const [tryLogin, { isLoading, isError, error }] = useLoginBySecretMutation();

  const onSuccessLogin = (payload) => {
    if (payload.type !== 'success' || !payload.refresh_token) {
      setMessage('Произошла неизвестная ошибка');
    } else {
      setMessage('Успешная авторизация. Редирект...');
      auth(payload);
      setConfigs(payload.configs);
      timer.current = setTimeout(() => navigate('/dashboard'), 1000);
    }
  };

  useEffect(() => {
    if (isAuth) navigate('/dashboard');
    if (secret) {
      tryLogin(secret).unwrap().then(onSuccessLogin);
    }
    return () => clearTimeout(timer.current);
  }, []);

  return (
    <div className={styles.h100}>
      <div className={styles.topBlock}>
        <div className={styles.text}>
          {isLoading && 'Пытаюсь войти...'}
          {isError && error.data?.message}
          {!isLoading && !isError && message}
        </div>
      </div>
      <div className={styles.bottonBlock}></div>
    </div>
  );
};

export default LoginPage;
