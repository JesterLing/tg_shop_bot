import React from 'react';

import useTitle from '../../hooks/useTitle';
import styles from '../Login/Login.module.less';

const NotFound = () => {
  useTitle('404');
  return (
    <div className={styles.h100}>
      <div className={styles.topBlock}>
        <div className={styles.text}>Страница не найдена</div>
      </div>
      <div className={styles.bottonBlock}></div>
    </div>
  );
};

export default NotFound;
