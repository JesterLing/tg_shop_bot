import React from 'react';

import styles from './Footer.module.less';

export default function Footer() {
  return (
    <footer className="footer">
      <small>v{process.env.VERSION}</small> Created with{' '}
      <i className={`petalicon petalicon-heart ${styles.heart}`}></i> by{' '}
      <a href="tg://user?name=@jester.ling">@jester.ling</a>
    </footer>
  );
}
