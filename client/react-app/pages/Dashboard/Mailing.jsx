import React, { useRef, useState } from "react";

import PopupEmojiPicker from "../../components/PopupEmojiPicker/PopupEmojiPicker";
import Button from "../../components/UI/Button";
import { useMailingMutation } from "../../service/API";
import styles from "./Mailing.module.less";
import Alert from "../../components/UI/Alert";

const Mailing = () => {
    const [mailing, setMailing] = useState('');
    const [message, setMessage] = useState('');
    const emojiBtnRef = useRef(null);
    const [send, { isLoading, isError, error }] = useMailingMutation();

    const handleSubmit = () => {
        if(mailing) {
            send({'message': mailing}).unwrap().then(result => {
                if (result.type == 'success') {
                  setMessage(result.message);
                }
            });
        }
    } 

    return (
        <div>
            <h4 className="mt-20">Рассылка</h4>
            { (isError || message) && <Alert type={`${isError ? "danger" : "okay"}`} icon={true} delay={5000}>{error}{message}</Alert> }
            <div className="row">
                <div className="col-2">
                    <div className="form-group">
                        <label className="form-label" htmlFor="mailing">Сообщение</label>
                        <div className={styles.textareaWrapper}>
                            <textarea className="input" name="mailing" id="mailing" rows={8} value={mailing} onChange={(e) => setMailing(e.target.value)}></textarea>
                            <div className={styles.emojiBtn} ref={emojiBtnRef}>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 36"><path fill="#FFCC4D" d="M36 18c0 9.941-8.059 18-18 18S0 27.941 0 18 8.059 0 18 0s18 8.059 18 18" /><path fillRule="evenodd" clipRule="evenodd" fill="#292F33" d="M1.24 11.018c.24.239 1.438.957 1.677 1.675.24.717.72 4.784 2.158 5.981 1.483 1.232 7.077.774 8.148.24 2.397-1.195 2.691-4.531 3.115-6.221.239-.957 1.677-.957 1.677-.957s1.438 0 1.678.956c.424 1.691.72 5.027 3.115 6.221 1.072.535 6.666.994 8.151-.238 1.436-1.197 1.915-5.264 2.155-5.982.238-.717 1.438-1.435 1.677-1.674.241-.239.241-1.196 0-1.436-.479-.478-6.134-.904-12.223-.239-1.215.133-1.677.478-4.554.478-2.875 0-3.339-.346-4.553-.478-6.085-.666-11.741-.24-12.221.238-.239.239-.239 1.197 0 1.436z" /><path fill="#664500" d="M27.335 23.629c-.178-.161-.444-.171-.635-.029-.039.029-3.922 2.9-8.7 2.9-4.766 0-8.662-2.871-8.7-2.9-.191-.142-.457-.13-.635.029-.177.16-.217.424-.094.628C8.7 24.472 11.788 29.5 18 29.5s9.301-5.028 9.429-5.243c.123-.205.084-.468-.094-.628z" /></svg>
                            </div>
                            <PopupEmojiPicker
                                buttonRef={emojiBtnRef}
                                previewPosition="none"
                                onEmojiSelect={(emoji) => setMailing(mailing + emoji.native)}
                            />
                        </div>
                        <p className="form-caption">
                            &lt;i&gt;курсив&lt;/i&gt; - <i>курсив</i><br />
                            &lt;b&gt;жирный&lt;/b&gt; - <b>жирный</b><br />
                            &lt;u&gt;подчеркнутый&lt;/u&gt; - <u>подчеркнутый</u><br />
                            &lt;s&gt;зачеркнутый&lt;/s&gt; - <s>зачеркнутый</s><br />
                            &lt;a href="http://www.example.com/"&gt;ссылка&lt;/a&gt; - <a href="http://www.example.com/" target="_blank">ссылка</a><br />
                            {`{NAME}`} - будет вставлено имя пользователя<br />
                            {`{USER_TELEGRAM_ID}`} - будет вставлено ID пользователя
                        </p>
                    </div>
                </div>
                <div className="col-2">
                    <div className="form-label">Предпросмотр</div>
                    <div className={styles.preview} dangerouslySetInnerHTML={{__html: mailing.replace(/\r\n|\r|\n/g,"<br />")}}></div>
                </div>
                <div className="col-1">
                    <div className="form-group" style={{ textAlign: 'right' }}>
                        <Button text="Отправить" color="blue" onClick={handleSubmit} disabled={isLoading}/>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default Mailing;
