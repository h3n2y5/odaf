package com.mdaf.odaf;

import android.annotation.SuppressLint;
import android.os.Bundle;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.webkit.WebResourceError;
import android.webkit.WebResourceRequest;
import android.widget.Toast;
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
        // Contoh: http://192.168.1.15:8080
        // Atau biarkan http://10.0.2.2:8080 jika Anda menggunakan Android Emulator (localhost).
        webView.loadUrl("http://10.0.2.2:8080");
        
        webView.setWebViewClient(new WebViewClient() {
            @Override
            public void onReceivedError(WebView view, WebResourceRequest request, WebResourceError error) {
                super.onReceivedError(view, request, error);
                
                // Hanya tangkap error halaman utama
                if (request.isForMainFrame()) {
                    String errorHtml = "<html><body style='padding: 20px; text-align: center; font-family: sans-serif;'>" +
                                       "<h2 style='color: red;'>Koneksi Gagal</h2>" +
                                       "<p>Pesan: <b>" + error.getDescription() + "</b></p>" +
                                       "<p>Pastikan server ODAF (start.bat) sudah berjalan di komputer host Anda.</p>" +
                                       "</body></html>";
                    view.loadData(errorHtml, "text/html", "UTF-8");
                    
                    Toast.makeText(MainActivity.this, "Koneksi Error: " + error.getDescription(), Toast.LENGTH_LONG).show();
                }
            }
        });
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
