import React from 'react';
import Button from '../UI/Button';

const Upload = ({ name, fileRef, inputRef, error, ...props }) => {
  return (
    <div className={`form-group${error ? ' validation error' : ''}`}>
      <input type="file" style={{ display: 'none' }} ref={fileRef} {...props} />
      <label className="form-label" htmlFor={props.id}>
        {name}
      </label>
      <div className="input-group">
        <input type="text" className="input" id={props.id} ref={inputRef} readOnly={true} />
        <span className="input-addon-btn">
          <Button text="Обзор" color="blue" onClick={() => fileRef.current?.click()} />
        </span>
      </div>
      {error && <p className="form-caption">{error}</p>}
    </div>
  );
};

export default Upload;
