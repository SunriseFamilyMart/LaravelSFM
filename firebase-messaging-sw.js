importScripts('https://www.gstatic.com/firebasejs/8.3.2/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/8.3.2/firebase-messaging.js');

firebase.initializeApp({
    apiKey: "AIzaSyDGLlD4u8STeR6VPz312yWAG2iP0OkFXsc",
    authDomain: "subh1-9a9f1.firebaseapp.com",
    projectId: "subh1-9a9f1",
    storageBucket: "subh1-9a9f1.firebasestorage.app",
    messagingSenderId: "770962795360",
    appId: "1:770962795360:android:7d8ca2b6a6cb8c5a730c05",
    measurementId: "G-FHZZZWDCHM"
});

const messaging = firebase.messaging();
messaging.setBackgroundMessageHandler(function (payload) {
    return self.registration.showNotification(payload.data.title, {
        body: payload.data.body ? payload.data.body : '',
        icon: payload.data.icon ? payload.data.icon : ''
    });
});