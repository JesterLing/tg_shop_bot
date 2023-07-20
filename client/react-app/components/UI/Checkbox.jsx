import React, { forwardRef } from 'react';

export default forwardRef(({ label = null, id, error, caption = null, ...props }, ref) => {
  return (
    <div className="form-group">
      <input
        type="checkbox"
        className={`checkbox${error ? ' validation error' : ''}`}
        id={id}
        ref={ref}
        {...props}
      />
      {label && <label htmlFor={id}>{label}</label>}
      {caption && <p className="form-caption">{caption}</p>}
    </div>
  );
});
