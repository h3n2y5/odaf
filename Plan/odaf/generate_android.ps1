$targetDir = "d:\OFFICE\MDAF\Plan\odaf-android"
New-Item -ItemType Directory -Force -Path "$targetDir\app\src\main\java\com\mdaf\odaf" | Out-Null
New-Item -ItemType Directory -Force -Path "$targetDir\app\src\main\res\values" | Out-Null
New-Item -ItemType Directory -Force -Path "$targetDir\app\src\main\res\layout" | Out-Null

# settings.gradle
@"
pluginManagement {
    repositories {
        google()
        mavenCentral()
        gradlePluginPortal()
    }
}
dependencyResolutionManagement {
    repositoriesMode.set(RepositoriesMode.FAIL_ON_PROJECT_REPOS)
    repositories {
        google()
        mavenCentral()
    }
}
rootProject.name = "ODAF"
include ':app'
"@ | Out-File -FilePath "$targetDir\settings.gradle" -Encoding utf8

# build.gradle (Project)
@"
// Top-level build file
plugins {
    id 'com.android.application' version '8.2.0' apply false
}
"@ | Out-File -FilePath "$targetDir\build.gradle" -Encoding utf8

# gradle.properties
@"
org.gradle.jvmargs=-Xmx2048m -Dfile.encoding=UTF-8
android.useAndroidX=true
android.nonTransitiveRClass=true
"@ | Out-File -FilePath "$targetDir\gradle.properties" -Encoding utf8

# app/build.gradle
@"
plugins {
    id 'com.android.application'
}

android {
    namespace 'com.mdaf.odaf'
    compileSdk 34

    defaultConfig {
        applicationId "com.mdaf.odaf"
        minSdk 24
        targetSdk 34
        versionCode 1
        versionName "1.0"
    }

    buildTypes {
        release {
            minifyEnabled false
            proguardFiles getDefaultProguardFile('proguard-android-optimize.txt'), 'proguard-rules.pro'
        }
    }
    compileOptions {
        sourceCompatibility JavaVersion.VERSION_1_8
        targetCompatibility JavaVersion.VERSION_1_8
    }
}

dependencies {
    implementation 'androidx.appcompat:appcompat:1.6.1'
    implementation 'com.google.android.material:material:1.11.0'
}
"@ | Out-File -FilePath "$targetDir\app\build.gradle" -Encoding utf8

# AndroidManifest.xml
@"
<?xml version="1.0" encoding="utf-8"?>
<manifest xmlns:android="http://schemas.android.com/apk/res/android"
    package="com.mdaf.odaf">

    <uses-permission android:name="android.permission.INTERNET" />

    <application
        android:allowBackup="true"
        android:icon="@mipmap/ic_launcher"
        android:label="@string/app_name"
        android:roundIcon="@mipmap/ic_launcher_round"
        android:supportsRtl="true"
        android:theme="@style/Theme.ODAF"
        android:usesCleartextTraffic="true">
        <activity
            android:name=".MainActivity"
            android:exported="true"
            android:theme="@style/Theme.ODAF.NoActionBar">
            <intent-filter>
                <action android:name="android.intent.action.MAIN" />
                <category android:name="android.intent.category.LAUNCHER" />
            </intent-filter>
        </activity>
    </application>

</manifest>
"@ | Out-File -FilePath "$targetDir\app\src\main\AndroidManifest.xml" -Encoding utf8

# MainActivity.java
@"
package com.mdaf.odaf;

import android.annotation.SuppressLint;
import android.os.Bundle;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import androidx.appcompat.app.AppCompatActivity;

public class MainActivity extends AppCompatActivity {

    private WebView webView;

    @SuppressLint("SetJavaScriptEnabled")
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        
        webView = new WebView(this);
        setContentView(webView);

        WebSettings webSettings = webView.getSettings();
        webSettings.setJavaScriptEnabled(true);
        webSettings.setDomStorageEnabled(true);

        // Ganti URL ini dengan IP Address lokal komputer Anda. 
        // Contoh: http://192.168.1.15:8000
        // Atau biarkan http://10.0.2.2:8000 jika Anda menggunakan Android Emulator (localhost).
        webView.loadUrl("http://10.0.2.2:8000");
        
        webView.setWebViewClient(new WebViewClient());
    }

    @Override
    public void onBackPressed() {
        if (webView.canGoBack()) {
            webView.goBack();
        } else {
            super.onBackPressed();
        }
    }
}
"@ | Out-File -FilePath "$targetDir\app\src\main\java\com\mdaf\odaf\MainActivity.java" -Encoding utf8

# strings.xml
@"
<resources>
    <string name="app_name">ODAF</string>
</resources>
"@ | Out-File -FilePath "$targetDir\app\src\main\res\values\strings.xml" -Encoding utf8

# themes.xml
@"
<resources xmlns:tools="http://schemas.android.com/tools">
    <style name="Theme.ODAF" parent="Theme.MaterialComponents.DayNight.DarkActionBar">
        <item name="colorPrimary">@color/purple_500</item>
        <item name="colorPrimaryVariant">@color/purple_700</item>
        <item name="colorOnPrimary">@color/white</item>
    </style>
    <style name="Theme.ODAF.NoActionBar" parent="Theme.MaterialComponents.DayNight.NoActionBar">
        <item name="windowActionBar">false</item>
        <item name="windowNoTitle">true</item>
    </style>
</resources>
"@ | Out-File -FilePath "$targetDir\app\src\main\res\values\themes.xml" -Encoding utf8

# colors.xml
@"
<?xml version="1.0" encoding="utf-8"?>
<resources>
    <color name="purple_200">#FFBB86FC</color>
    <color name="purple_500">#FF6200EE</color>
    <color name="purple_700">#FF3700B3</color>
    <color name="teal_200">#FF03DAC5</color>
    <color name="teal_700">#FF018786</color>
    <color name="black">#FF000000</color>
    <color name="white">#FFFFFFFF</color>
</resources>
"@ | Out-File -FilePath "$targetDir\app\src\main\res\values\colors.xml" -Encoding utf8

Write-Host "Android project generated successfully at $targetDir"
