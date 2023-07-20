import React from "react";

const Button = ({ text = "", color = "blue", type = "button", className = null, icon = null, hollow = false, compact = false, ...props }) => {
    var classes = ['btn', color];
    if(icon) classes.push("has-icon");
    if(hollow) classes.push("hollow");
    if(compact) classes.push("compact");
    if(className) classes.push(className);
    return (
        <button type={type} className={classes.join(" ")} {...props}>{icon && <i className={`petalicon petalicon-${icon}`}></i>} {text}</button>
    );
  };


export default Button;