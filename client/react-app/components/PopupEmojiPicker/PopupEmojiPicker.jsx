import React, { useState } from "react";
import { useSelector } from "react-redux";
import Picker from '@emoji-mart/react';

import { useEmojiSetQuery, useEmojiI18nQuery } from "../../service/API";
import styles from "./PopupEmojiPicker.module.less";

export default ({ buttonRef, ...props }) => {
  const { themeDark } = useSelector((state) => state.global);
  const { data : emojiData, isSuccess : dataSuccess } = useEmojiSetQuery();
  const { data : emojiI18n, isSuccess: i18nSuccess } = useEmojiI18nQuery();
  const [emojiVisible, setEmojiVisible] = useState(false);

  const onClickOut = (e) => {
    if(buttonRef.current) {
      buttonRef.current.contains(e.target) ? setEmojiVisible(!emojiVisible) : setEmojiVisible(false);
    }
  }
  return(
    <div className={`${styles.container} ${emojiVisible ? styles.visible : styles.hide}`}>
      { (dataSuccess && i18nSuccess) ?
          <Picker
          data={JSON.parse(JSON.stringify(emojiData))}
          i18n={emojiI18n}
          emojiVersion={14}
          previewPosition="none"
          set="native"
          theme={themeDark ? "dark" : "light"}
          onClickOutside={onClickOut}
          {...props} 
        /> :
        null
      }
    </div>
  );
}
