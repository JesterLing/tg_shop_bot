import React from "react";
import Button from "./Button";

const LoadingButton = ({ loading, success, className = null, ...props }) => {
  var classes = ["btn-spinner"];
  if(loading && !success) classes.push("loading");
  if(!loading && success) classes.push("loading-done");
  if(className) classes.push(className);

  return (<Button className={classes.join(" ")} {...props} />);
}

export default LoadingButton;