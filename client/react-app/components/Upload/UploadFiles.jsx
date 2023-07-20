import React, { useEffect, useRef } from 'react';
import { useSelector } from 'react-redux';

import Button from '../UI/Button';
import Upload from './Upload';
import { useUploadMutation } from '../../service/API';
import { formatBytes } from '../Utils';

const UploadFiles = ({ files, setFiles }) => {
  const inputRef = useRef(null);
  const filesRef = useRef(null);
  const { uploadProgress } = useSelector((state) => state.global);

  const [upload, { isLoading, isError, error }] = useUploadMutation();

  useEffect(() => {
    inputRef.current.value = '';
    inputRef.current.value += files.map(function (itm) {
      return itm?.name;
    });
  }, [files]);

  const onChangeHandler = async (e) => {
    if (!e.target.files) return;
    let formData = new FormData();
    for (var i = 0; i < e.target.files.length; i++) {
      formData.append('files[]', e.target.files[i]);
    }
    const data = await upload(formData).unwrap();
    if (data.type == 'success') {
      setFiles(files.concat(data.update));
    }
  };

  return (
    <div>
      <Upload
        inputRef={inputRef}
        fileRef={filesRef}
        error={error}
        name="Файлы"
        id="files"
        onChange={onChangeHandler}
        multiple={true}
      />

      {uploadProgress != 0 && uploadProgress != 100 && (
        <div>
          <span className="spinner spinning">
            <span></span>
          </span>{' '}
          Загрузка... [{uploadProgress}%]
        </div>
      )}
      {isLoading && uploadProgress == 100 && (
        <div>
          <span className="spinner spinning">
            <span></span>
          </span>{' '}
          Обработка...
        </div>
      )}
      <ul>
        {files.map((file, index) => (
          <li key={index} style={{ listStyle: 'decimal' }}>
            <div>
              <a href={file?.path} target="_blank" rel="noreferrer" style={{ marginRight: '10px' }}>
                {file?.name}
              </a>
              <Button
                icon="trash"
                color="red"
                compact={true}
                hollow={true}
                onClick={() => setFiles(files.filter((item) => item.id !== file?.id))}
              />
            </div>
            <div>{formatBytes(file?.size)}</div>
          </li>
        ))}
      </ul>
    </div>
  );
};

export default UploadFiles;
