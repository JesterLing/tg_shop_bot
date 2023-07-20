import React, { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useSelector } from 'react-redux';

export const formatBytes = (bytes, decimals = 2) => {
  if (!+bytes) return '0 Bytes';
  const k = 1024;
  const dm = decimals < 0 ? 0 : decimals;
  const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
};

export const getCookie = (name) => {
  return document.cookie
    .split('; ')
    .filter((row) => row.startsWith(name + '='))
    .map((c) => c.split('=')[1])[0];
};

export const delCookies = () => {
  document.cookie.split(';').forEach(function (c) {
    document.cookie = c.trim().split('=')[0] + '=;' + 'expires=Thu, 01 Jan 1970 00:00:00 UTC;';
  });
};

export const ProtectedLayout = ({ children }) => {
  const navigate = useNavigate();
  const { isAuth } = useSelector((state) => state.global);
  useEffect(() => {
    if (!isAuth) {
      return navigate('/');
    }
  }, []);
  return children;
};

export const PublicLayout = ({ children }) => {
  const navigate = useNavigate();
  const { isAuth } = useSelector((state) => state.global);
  useEffect(() => {
    if (isAuth) {
      return navigate('/dashboard');
    }
  }, []);

  return children;
};

export const ConditionalWrapper = ({ condition, wrapper, children }) => {
  return condition ? wrapper(children, condition) : children;
};

export const formatTimesmap = (timesmap) => {
  return new Intl.DateTimeFormat('default', {
    year: 'numeric',
    month: 'numeric',
    day: 'numeric',
    hour: 'numeric',
    minute: 'numeric',
    second: 'numeric',
    hour12: false
  }).format(timesmap * 1e3);
};

export const collectUserName = ({ username, first_name, last_name }, full = true) => {
  let result = '';
  if (full) {
    if (first_name) {
      result += first_name;
    }
    if (last_name) {
      result !== '' ? (result += ' ' + last_name) : (result += last_name);
    }
    if (username) {
      result !== '' ? (result += ' (@' + username + ')') : (result += username);
    }
  } else {
    if (username) {
      result = '@' + username;
    } else {
      if (first_name) {
        result = first_name;
      }
      if (last_name) {
        result !== '' ? (result += ' ' + last_name) : (result = last_name);
      }
    }
  }
  return result;
};
