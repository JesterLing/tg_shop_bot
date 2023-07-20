import React, { forwardRef } from 'react';
import { ConditionalWrapper } from '../Utils';

const Input = forwardRef(
  (
    {
      label = null,
      type = 'text',
      error = null,
      caption = null,
      inline = false,
      addon = null,
      ...props
    },
    ref
  ) => (
    <div className={`form-group${error ? ' validation error' : ''}`}>
      {label && (
        <label className="form-label" htmlFor={`${props.id ? props.id : ''}`}>
          {label}
          {error && inline ? (
            <span className="form-caption">{error}</span>
          ) : (
            caption && inline && <span className="form-caption">{caption}</span>
          )}
        </label>
      )}
      <ConditionalWrapper
        condition={addon}
        wrapper={(children, cond) => (
          <div className="input-group">
            <span className="input-addon">{cond}</span>
            {children}
          </div>
        )}>
        <input
          className="input"
          htmlFor={`${props.id && label ? props.id : ''}`}
          type={type}
          ref={ref}
          {...props}
        />
      </ConditionalWrapper>
      {error && !inline ? (
        <p className="form-caption">{error}</p>
      ) : (
        caption && !inline && <p className="form-caption">{caption}</p>
      )}
    </div>
  )
);

export default Input;
