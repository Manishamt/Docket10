<?php

namespace Drupal\document_to_pdf\Form;

use Drupal\Core\Form\FormBase;
use Drupal\node\Entity\Node;
use PhpOffice\PhpWord\PhpWord;
use Dompdf\Dompdf;
use Drupal\file\Entity\File;
use Dompdf\Options;
use PhpOffice\PhpWord\IOFactory;
use Drupal\Core\Form\FormStateInterface;




class DocketSubmissionForm extends FormBase
{




  public function getFormId()
  {
    return 'docket_submission_form';
  }


  public function buildForm(array $form, FormStateInterface $form_state, $title = NULL)
  {
    if (!$title) {
      $title = \Drupal::request()->query->get('title');
    }

    if (!$title) {
      // Extract title from the current path.
      $path = \Drupal::service('path.current')->getPath();
      $nodeId = basename($path);
    }

    $node = Node::load($nodeId);
    if ($node) {
      $title = $node->get('title')->value;
      $nid = $node->id();
      $dateRange = $node->get('field_dock_date_submission')->value;
    }
    $form['title'] = [
      '#type' => 'hidden',
      '#value' => $title,
    ];


    if ($title) {
      $form['title_display'] = [
        '#type' => 'item',
        '#markup' => '<h2>' . $title . '</h2>',
      ];
    }

    $form['field_body'] = [
      '#type' => 'textarea',

      '#title' => $this->t('<h5>Comment</h5>'),
      '#prefix' => '<div><h3>Your Comment</h3><br>Your comment can be entered into the comment field or uploaded as a text or PDF file.<br><br><div style="color: #321e00; background-color: #f9f1c6;">OEHHA is subject to the California Public Records Act and other laws that require the release of certain information upon request. If you provide comments, please be aware that your name, address and e-mail may be available to third parties. In addition, all public comments will be posted on our web site upon the close of the comment period.</div></div>',

      '#required' => FALSE,
    ];



    // Use file upload instead of media entity.
    $form['field_upload_comment_file'] = [
      '#type' => 'managed_file',
      '#upload_location' => 'public://',
      '#multiple' => TRUE,
      '#title' => $this->t('Upload Comment'),
      '#description' => $this->t('Upload up to 10 files. Allowed file types: pdf, doc, docx, rtf, txt.'),
      '#upload_validators' => [
        'file_validate_extensions' => ['pdf doc docx rtf txt'],
        'file_validate_size' => [25600000], // 25MB limit
      ],
    ];

    $form['field_docksub_name_publish'] = [
      '#type' => 'textfield',
      '#title' => $this->t('<h5>Published Name</h5>'),
      '#description' => $this->t('How would you like your comment displayed once published? Please enter the name of your organization or your name as you would like it to appear.
      '),

    ];
    $form['field_docksub_email'] = [
      '#type' => 'email',
      '#title' => $this->t('<h5>Email</h5>'),
      '#description' => $this->t('Including your e-mail address is optional but enables us to contact you in the event there is a problem with your submission.
      '),

    ];


    $form['field_uploaded_comment_convert_f'] = [
      '#type' => 'managed_file',
      '#upload_location' => 'public://',
      '#multiple' => TRUE,
      '#title' => $this->t('<h3>Upload private.</h3>'),
      '#description' => $this->t('Upload up to 10 files. Allowed file types: pdf doc docx rtf txt.'),
      '#upload_validators' => [
        'file_validate_extensions' => ['pdf'],
        'file_validate_size' => [25600000], // 25MB limit
      ],
      '#access' => FALSE,
    ];
    $form['field_uploaded_comment_convert_t'] = [
      '#type' => 'managed_file',
      '#upload_location' => 'public://',
      '#multiple' => TRUE,
      '#title' => $this->t('<h3>Upload not private.</h3>'),
      '#description' => $this->t('Upload up to 10 files. Allowed file types: pdf doc docx rtf txt.'),
      '#upload_validators' => [
        'file_validate_extensions' => ['pdf'],
        'file_validate_size' => [25600000], // 25MB limit
      ],
      '#access' => FALSE,
    ];
    $form['field_comment_saved_as_pdf_file'] = [
      '#type' => 'managed_file',
      '#upload_location' => 'public://',
      '#multiple' => TRUE,
      '#title' => $this->t('<h3>Upload comment saved as pdf.</h3>'),
      '#description' => $this->t('Upload up to 10 files. Allowed file types: pdf doc docx rtf txt.'),
      '#upload_validators' => [
        'file_validate_extensions' => ['pdf'],
        'file_validate_size' => [25600000], // 25MB limit
      ],
      '#access' => FALSE,
    ];


   $form['field_docksub_docket_item'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('<h5>Docket Item</h5>'),
      '#target_type' => 'node',
      '#default_value' => $node,
      '#selection_settings' => [
        'target_bundles' => ['docket_item'],
      ],
      '#access' => FALSE,
    ];

 
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit Comment'),
    ];


    return $form;
  }


  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    // Get form values.
    $values = $form_state->getValues();

    // Check if the required fields are empty.
    if (empty($values['field_body']) || empty($values['field_docksub_name_publish']) || empty($values['field_upload_comment_file'])) {
      // Add form error if any of the required fields are empty.
      $form_state->setErrorByName('', $this->t('All fields are  required .'));
    }
  }



  public function submitForm(array &$form, $form_state)
  {
    $node = $this->customSubmit($form, $form_state);
    $form_state->setRedirect('entity.node.canonical', ['node' => $node->id()]);
  }

  public function customSubmit($form, $form_state)
  {
    $values = $form_state->getValues();

    // Collect file IDs from the file fields instead of media fields.
    $attachment_ids = $values['field_upload_comment_file'];
    $converted_private_ids = $values['field_uploaded_comment_convert_f'];
    $comment_as_pdf_ids = $values['field_comment_saved_as_pdf_file'];

    // Create a node with the file field values directly.
    $node = Node::create([
      'type' => 'docket_submission',
      'title' => $values['title'],
      'field_body' => $values['field_body'],
      'field_docksub_name_publish' => $values['field_docksub_name_publish'],
      'field_upload_comment_file' => $attachment_ids,
      'field_docksub_email' => $values['field_docksub_email'],
      'field_uploaded_comment_convert_f' => $converted_private_ids,
      'field_comment_saved_as_pdf_file' => $comment_as_pdf_ids,
      'field_docksub_docket_item' => $values['field_docksub_docket_item'],
      'field_docksub_final_pdf' => $values['field_docksub_final_pdf'],
    ]);

    $node->save();

    $node->setTitle('Comment - ' . $node->id() . ' - ' . $values['field_docksub_name_publish']);
    $node->save();

    // Convert documents to PDF and other actions.
    $this->document_to_pdf_convert_to_pdf($node);
    $this->generate_comment_pdf($node);
    $this->merge_and_save_pdf_files($node);

    return $node;
  }


  /**
   * Convert files to PDF format.
   */
  function document_to_pdf_convert_to_pdf($node)
  {
    // Get the file field values from the node.
    $field_values = $node->get('field_upload_comment_file')->getValue();

    // Iterate through each file attached in the field.
    foreach ($field_values as $value) {
      if (isset($value['target_id'])) {
        $file = \Drupal\file\Entity\File::load($value['target_id']);

        if ($file) {
          // Get the MIME type of the file.
          $mime_type = $file->getMimeType();

          // Check the MIME type and handle the conversion accordingly.
          switch ($mime_type) {
            case 'application/msword': // .doc
              // Call function to convert .doc to PDF.
              convert_doc_to_pdf($file, $node);
              break;

            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document': // .docx
              // Call function to convert .docx to PDF.
              convert_docx_to_pdf($file, $node);
              break;

            case 'text/plain': // .txt
              // Call function to convert plain text to PDF.
              convert_plain_text_to_pdf($file, $node);
              break;

            case 'application/rtf': // .rtf
              // Call function to convert RTF text to PDF.
              convert_rtf_text_to_pdf($file, $node);
              break;

              // Add other MIME types as needed.
            default:
              // Unsupported file type, you can log a message or take other action if needed.
              break;
          }
        }
      }
    }
  }




  /**
   * Generate PDF containing comment fields.
   */
  function generate_comment_pdf($node)
  {
    $request = \Drupal::request();

    // Check if the title parameter exists in the URL.
    // $title = $request->query->get('title');
    $docket_item_entity = $node->get('field_docksub_docket_item')->entity;
    // Get the title or any other relevant information from the Docket Item entity.
    $docket_item_title = !empty($docket_item_entity) ? $docket_item_entity->label() : '';

    $name = $node->get('field_docksub_name_publish')->value;
    $email = $node->get('field_docksub_email')->value;
    $created = $node->get('created')->value;
    $formatted_created = \Drupal::service('date.formatter')->format($created, 'custom', 'm/d/Y - g:i a');
    $comment = $node->get('field_body')->value;

    $phpWord = new PhpWord();
    $section = $phpWord->addSection();
    $section->addText("<h1> $docket_item_title </h1>");
    $section->addText("Published Name  : <br> $name");
    $section->addText("Email: <br> $email");
    $section->addText("Post date: <br> $formatted_created");
    $section->addText("Comment:<br> $comment");

    try {
      $options = new Options();
      $options->set('isHtml5ParserEnabled', true);
      $dompdf = new Dompdf($options);

      // Save PhpWord content to HTML
      $tempHtmlFile = tempnam(sys_get_temp_dir(), 'phpword_to_html');
      $xmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML');
      $xmlWriter->save($tempHtmlFile);
      $htmlContent = file_get_contents($tempHtmlFile);
      unlink($tempHtmlFile);

      $dompdf->loadHtml($htmlContent);
      $dompdf->setPaper('A4', 'portrait');
      $dompdf->render();
      $pdfContent = $dompdf->output();

      $pdfFilePath = 'public://commentfields_' . $node->id() . '.pdf';
      file_put_contents($pdfFilePath, $pdfContent);

      $file = File::create([
        'uri' => $pdfFilePath,
      ]);
      $file->save();

      $node->field_comment_saved_as_pdf_file->setValue(['target_id' => $file->id()]);
      $node->save();




      \Drupal::messenger()->addMessage(t('PDF file generated successfully for node ID: @nid', ['@nid' => $node->id()]));
    } catch (\Exception $e) {
      // Log error.
      \Drupal::logger('document_to_pdf')->error('Error generating PDF: @error', ['@error' => $e->getMessage()]);
      // Add error message.
      \Drupal::messenger()->addError(t('An error occurred while generating PDF: @error', ['@error' => $e->getMessage()]));
    }
  }



//   /**
//    * Merge all generated PDF files and save into field_docksub_final_pdf field.
//    */
  function merge_and_save_pdf_files($node)
  {
    // Fetch referenced entities from both fields
    $pdf_files_1 = $node->get('field_comment_saved_as_pdf_file')->referencedEntities();
    $pdf_files_2 = $node->get('field_uploaded_comment_convert_f')->referencedEntities();
    $pdf_files_3 = $node->get('field_upload_comment_file')->referencedEntities();

    // Merge entities from both fields
    $pdf_files = array_merge($pdf_files_1, $pdf_files_2);
    foreach ($pdf_files_3 as $pdf_file) {
      if ($pdf_file->getMimeType() === 'application/pdf') {
        $pdf_files[] = $pdf_file;
      }
    }
    // Initialize FPDI
    $pdf = new \setasign\Fpdi\Fpdi();

    foreach ($pdf_files as $pdf_file) {
      $pdf_file_uri = $pdf_file->getFileUri();
      $pageCount = $pdf->setSourceFile($pdf_file_uri);


      for ($pageNum = 1; $pageNum <= $pageCount; $pageNum++) {
        $templateId = $pdf->importPage($pageNum);
        $size = $pdf->getTemplateSize($templateId);
        $height = $size['height'] + 100;
        $width = $size['width'] + 100;
        $pdf->AddPage('P', [$width, $height]);
        $pdf->useTemplate($templateId);
      }
    }

    // Save merged PDF content to a new PDF file.
    $directory = 'public://';
    $file_path = $directory . 'merged_file_' . $node->id() . '.pdf';
    $pdf->Output($file_path, 'F');

    $pdf_file = File::create(['uri' => $file_path, 'status' => 1]);
    $pdf_file->save();

    $node->set('field_final_pdf_file', ['target_id' => $pdf_file->id(), 'display' => 1]);
    $node->save();
  }















 }
