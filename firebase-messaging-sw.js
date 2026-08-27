importScripts('https://www.gstatic.com/firebasejs/10.8.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.8.0/firebase-messaging-compat.js');

firebase.initializeApp({
    apiKey: "AIzaSyCXkLWCZD3vKkybvp41YyyU_G2vaeZRcs0",
    authDomain: "hae-fatec.firebaseapp.com",
    projectId: "hae-fatec",
    storageBucket: "hae-fatec.firebasestorage.app",
    messagingSenderId: "732325516207",
    appId: "1:732325516207:web:93cdd26e78656ec2ee156a"
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage(function(payload) {
  const notificationTitle = payload.notification.title;
  const notificationOptions = {
    body: payload.notification.body,
    icon: '/img/cps_fatecgarca_logo.jfif' // Logo da Fatec que aparece na notificação
  };
  return self.registration.showNotification(notificationTitle, notificationOptions);
});

