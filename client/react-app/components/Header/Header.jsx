import React, { useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useNavigate } from 'react-router-dom';
import { useSelector } from 'react-redux';

import Switch from '../../components/UI/Switch';
import { NavigationTop } from '../Navigation/Navigation';
import { useActions } from '../../hooks/useActions';
import { collectUserName } from '../Utils';

import styles from './Header.module.less';

const Header = () => {
  const { user, themeDark, menuFull } = useSelector((state) => state.global);
  const { toggleTheme, toggleMenu, deauth } = useActions();
  const navigate = useNavigate();

  useEffect(() => {
    if (menuFull) {
      document.body.classList.add('menu_left');
      document.body.classList.remove('menu_top');
    } else {
      document.body.classList.add('menu_top');
      document.body.classList.remove('menu_left');
    }
    themeDark ? document.body.classList.add('dark') : document.body.classList.remove('dark');
  }, [themeDark, menuFull]);

  const logOut = (e) => {
    e.preventDefault();
    deauth();
    navigate('/', { replace: true });
  };

  return (
    <header className="header">
      <Link to="/" className={styles.link}>
        <div className={styles.wrapper}>
          <div className={styles.logo}>
            <div className={styles.lside}></div>
            <div className={styles.rside}></div>
            <div className={styles.lwing}></div>
            <div className={styles.rwing}></div>
          </div>
        </div>
        <span className={styles.text}>
          BOT
          <br />
          ADMIN
          <br />
          PANNEL
        </span>
      </Link>
      <NavigationTop />
      <Switch
        labelLeft="Полное меню"
        id="menupos-toggle"
        onChange={() => toggleMenu()}
        checked={menuFull}
      />
      <Switch
        labelLeft="Темная тема"
        id="dark-toggle"
        onChange={() => toggleTheme()}
        checked={themeDark}
      />
      <div className={styles.user}>
        {user.photo && (
          <a href={`tg://user?id=${user.user_id}`} target="_blank" rel="noreferrer">
            <img src={user.photo} className={styles.photo} />
          </a>
        )}
        <a href={`tg://user?id=${user.user_id}`} target="_blank" rel="noreferrer">
          {collectUserName(user, false)}
        </a>
      </div>
      <a href="#" onClick={logOut} className={styles.exit}>
        Выйти
      </a>
    </header>
  );
};

export default Header;
