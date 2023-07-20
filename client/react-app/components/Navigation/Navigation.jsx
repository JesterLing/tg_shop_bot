import React, { useState } from 'react';

import { NavLink } from 'react-router-dom';

import styles from './Navigation.module.less';

export const NavigationTop = () => {
  const [mobileMenu, setmobileMenu] = useState(false);
    return(
    <nav className={styles.mainNav}>
      <div className={styles.mabileMainNav} onClick={() => setmobileMenu(!mobileMenu)}> <i className={`petalicon ${mobileMenu ? 'petalicon-cross': 'petalicon-list'}`}></i> {mobileMenu && 'Закрыть' || 'Меню' }</div>
			<ul className={mobileMenu ? styles.show: ''}>
				<li><NavLink to="/dashboard" className={({ isActive }) => isActive ? styles.active : ""}>Главная</NavLink></li>
				<li><NavLink to="/categories" className={({ isActive }) => isActive ? styles.active : ""}>Категории</NavLink></li>
        <li><NavLink to="/goods" className={({ isActive }) => isActive ? styles.active : ""}>Товары</NavLink></li>
        <li><NavLink to="/purchases" className={({ isActive }) => isActive ? styles.active : ""}>Покупки</NavLink></li>
        <li><NavLink to="/settings" className={({ isActive }) => isActive ? styles.active : ""}>Настройки</NavLink></li>
			</ul>
		</nav>
    );
}

export const NavigationLeft = () => {
  return (
      <nav className={styles.sideNav}>
      <h5 className={styles.sideNavHeader}>Меню</h5>
      <ul>
        <li><NavLink to="/dashboard" className={({ isActive }) => isActive ? styles.active : ""}><i className="petalicon petalicon-home"></i> Главная</NavLink></li>
        <li><NavLink to="/categories" className={({ isActive }) => isActive ? styles.active : ""}><i className="petalicon petalicon-list"></i> Категории</NavLink></li>
        <li><NavLink to="/goods" className={({ isActive }) => isActive ? styles.active : ""}><i className="petalicon petalicon-tile"></i> Товары</NavLink></li>
        <li><NavLink to="/purchases" className={({ isActive }) => isActive ? styles.active : ""}><i className="petalicon petalicon-cart"></i> Покупки</NavLink></li>
        <li><NavLink to="/settings" className={({ isActive }) => isActive ? styles.active : ""}><i className="petalicon petalicon-tools"></i> Настройки</NavLink></li>
      </ul>
      </nav>
  );
};