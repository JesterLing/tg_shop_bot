import React, { useState, useRef, useEffect } from 'react';

import Button from '../UI/Button';
import Upload from './Upload';
import { useUploadMutation } from '../../service/API';

import styles from './UploadImage.module.less';

const UploadImage = ({ image, setImage }) => {
  const imageRef = useRef(null);
  const inputRef = useRef(null);
  const [error, setError] = useState('');
  const [preview, setPreview] = useState('');

  const [upload, { isLoading, isError, error: uploadError }] = useUploadMutation();

  useEffect(() => {
    if (image) {
      if (image?.name) inputRef.current.value = image?.name;
      setPreview(image.path);
    } else {
      inputRef.current.value = '';
      setPreview('');
    }
  }, [image]);

  const validateFileEx = (name, exts) => {
    let idxDot = name.lastIndexOf('.') + 1,
      extFile = name.substr(idxDot, name.length).toLowerCase();
    if (exts.includes(extFile)) {
      return true;
    } else {
      return false;
    }
  };
  const onChangeHandler = async (e) => {
    onDeleteHandler();
    if (!e.target.files) return;
    if (e.target.files.length > 1) {
      setError('Можно выбрать только одно изображение');
      return false;
    }
    if (
      !validateFileEx(e.target.files[0].name, [
        'png',
        'jpg',
        'gif',
        'bmp',
        'heic',
        'heif',
        'jpeg',
        'tiff',
        'webp'
      ])
    ) {
      setError('Выбраный файл должен быть изображением');
      return false;
    }
    let formData = new FormData();
    formData.append('files[]', e.target.files[0]);
    const data = await upload(formData).unwrap();
    if (data.type == 'success') {
      setImage(data.update[0]);
    }
  };

  const onDeleteHandler = () => {
    setError('');
    setImage(null);
  };

  return (
    <div className={styles.upload}>
      <Upload
        inputRef={inputRef}
        fileRef={imageRef}
        error={isError ? uploadError : error}
        name="Изображение"
        accept="image/*"
        id="image"
        onChange={onChangeHandler}
      />
      <div className={styles.preview} style={{ backgroundImage: 'url(' + preview + ')' }}></div>
      <div className={styles.control}>
        <Button
          icon="eye"
          compact={true}
          disabled={!preview}
          onClick={() => window.open(preview, '_blank')}
        />
        <Button icon="trash" color="red" compact={true} onClick={onDeleteHandler} />
      </div>
    </div>
  );
};

export default UploadImage;
