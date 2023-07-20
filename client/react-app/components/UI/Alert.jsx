import React, { useState, useEffect } from 'react';

import styles from './Alert.module.less';

const Alert = ({
  children,
  delay = null,
  type = null,
  popup = false,
  icon = false,
  large = false
}) => {
  const [visible, setVisible] = useState(true);
  useEffect(() => {
    if (delay) {
      const timer = setTimeout(() => {
        setVisible(false);
      }, delay);
      return () => clearTimeout(timer);
    }
  }, [delay]);

  let iconHtml;
  if (icon && type) {
    switch (type) {
      case 'accent':
        iconHtml = <i className="petalicon petalicon-info"></i>;
        break;
      case 'okay':
        iconHtml = <i className="petalicon petalicon-check-round"></i>;
        break;
      case 'warning':
        iconHtml = <i className="petalicon petalicon-warning"></i>;
        break;
      case 'danger':
        iconHtml = <i className="petalicon petalicon-notice"></i>;
    }
  }
  if (visible) {
    return (
      <div
        className={`alert ${type ? type : ''} ${popup ? styles.popup : ''} ${
          large ? 'large' : ''
        }`}>
        {icon && <span className="alert-icon">{iconHtml}</span>}
        <span className="alert-text">{children}</span>
      </div>
    );
  } else {
    return null;
  }
};

export default Alert;
