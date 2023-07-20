import React, { forwardRef, Fragment } from 'react';

const Radio = forwardRef(({ id, label = null, value, caption = null, ...props }, ref) => (
  <Fragment>
    <input className="radiobox" type="radio" id={id} ref={ref} value={value} {...props} />
    {label && <label htmlFor={id}>{label}</label>}
    {caption && <p className="form-caption">{caption}</p>}
  </Fragment>
));

export default Radio;
